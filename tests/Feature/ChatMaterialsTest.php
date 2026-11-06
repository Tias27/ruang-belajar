<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\User;
use App\Services\ChatMaterials;
use App\Services\DocumentTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatMaterialsTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array', 'session.driver' => 'array',
        ]);
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Queue::fake();
        Http::preventStrayRequests();
        config([
            'services.ai.provider' => '9router', 'services.ai.fallback_provider' => 'none',
            'services.nine_router.api_key' => 'test-key',
            'services.nine_router.base_url' => 'https://router.test/v1',
            'services.nine_router.model' => 'ag/gemini-3.8-flash-medium',
        ]);
        Http::fake(['router.test/*' => Http::response(
            'data: {"choices":[{"delta":{"content":"Jawaban dari lampiran"},"finish_reason":"stop"}]}'."\n\n",
            200, ['Content-Type' => 'text/event-stream']
        )]);
    }

    private function document(User $user, array $attributes = []): Document
    {
        return Document::create(array_merge([
            'user_id' => $user->id, 'title' => 'Biologi',
            'original_name' => 'biologi.pdf', 'file_name' => 'test.pdf',
            'file_path' => 'documents/test.pdf', 'mime_type' => 'application/pdf',
            'extension' => 'pdf', 'size' => 100, 'status' => 'processed',
            'extracted_text' => 'Kloroplas mengandung klorofil untuk menangkap energi cahaya.',
        ], $attributes));
    }

    public function test_picker_lists_only_owned_readable_documents_and_paginates_and_searches(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        for ($i = 0; $i < 7; $i++) $this->document($user, ['title' => 'Biologi '.$i]);
        $this->document($user, ['extracted_text' => '']);
        $this->document(User::factory()->create(), ['title' => 'Rahasia']);

        $this->getJson(route('chat.materials.index'))->assertUnauthorized();
        $this->actingAs($user)->getJson(route('chat.materials.index'))
            ->assertOk()->assertJsonCount(5, 'items')->assertJsonPath('total', 7)->assertJsonPath('pages', 2);
        $this->getJson(route('chat.materials.index', ['page' => 2]))
            ->assertOk()->assertJsonCount(2, 'items')->assertJsonPath('page', 2);
        $this->getJson(route('chat.materials.index', ['q' => 'Biologi 3']))
            ->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.title', 'Biologi 3');
        $this->getJson(route('chat.materials.index', ['q' => 'Rahasia']))->assertJsonCount(0, 'items');
    }

    public function test_foreign_missing_unreadable_duplicate_and_excess_materials_are_rejected_before_chat_is_saved(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $owned = $this->document($user);
        $foreign = $this->document(User::factory()->create());
        $unreadable = $this->document($user, ['extracted_text' => '  ']);
        $this->actingAs($user);
        foreach ([[$foreign->public_id], ['does-not-exist'], [$unreadable->public_id], [$owned->public_id, $owned->public_id], range(1, 11)] as $ids) {
            $this->postJson(route('chat.general.store'), ['question' => 'Bahas', 'materials_present' => 1, 'material_ids' => $ids])
                ->assertUnprocessable();
        }
        $this->assertDatabaseCount('chat_sessions', 0);
        $this->assertDatabaseCount('chat_messages', 0);
        Http::assertNothingSent();
    }

    public function test_material_context_is_sent_persisted_restored_and_can_be_cleared(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $material = $this->document($user);
        $this->actingAs($user);
        $first = $this->postJson(route('chat.general.store'), [
            'question' => 'Bagaimana fotosintesis?', 'materials_present' => 1, 'material_ids' => [$material->public_id],
        ]);
        $first->assertOk();
        $this->assertStringContainsString('"done":true', $first->streamedContent());
        $session = ChatSession::firstOrFail();
        $this->assertNull($session->document_id);
        $this->assertSame($material->public_id, $session->messages()->where('role', 'user')->first()->metadata['materials'][0]['id']);
        $this->assertSame($material->id, $session->messages()->where('role', 'assistant')->first()->metadata['source_snippets'][0]['document_id']);
        $this->assertSame([$material->id], app(ChatMaterials::class)->forSession($session)->modelKeys());
        $this->get(route('chat.show', $session))->assertOk()->assertSee('Tambahkan materi')->assertSee($material->title);
        $this->postJson(route('chat.store', $session), ['question' => 'Lalu fungsinya?'])->streamedContent();
        $followup = $session->messages()->where('role', 'user')->latest('id')->first();
        $this->assertSame([], $followup->metadata['materials']);
        $this->assertSame($material->public_id, $followup->metadata['context_materials'][0]['id']);
        $this->assertSame([$material->id], app(ChatMaterials::class)->forSession($session)->modelKeys());
        $this->postJson(route('chat.store', $session), ['question' => 'Bahas topik lain', 'materials_present' => 1])->streamedContent();
        $requests = Http::recorded()->map(fn ($pair) => $pair[0]['messages'][1]['content'][0]['text']);
        $this->assertCount(3, $requests);
        $this->assertStringContainsString($material->extracted_text, $requests[0]);
        $this->assertStringContainsString($material->extracted_text, $requests[1]);
        $this->assertStringNotContainsString($material->extracted_text, $requests[2]);
        $this->assertTrue(app(ChatMaterials::class)->forSession($session)->isEmpty());
        $this->assertDatabaseCount('chat_sessions', 1);
        Queue::assertNothingPushed();
    }

    public function test_adding_material_keeps_existing_document_and_folder_context(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $folder = DocumentFolder::create(['user_id' => $user->id, 'name' => 'Sains']);
        $base = $this->document($user, ['folder_id' => $folder->id, 'extracted_text' => 'SUMBER_ASLI']);
        $extra = $this->document($user, ['extracted_text' => 'LAMPIRAN_TAMBAHAN']);
        $this->actingAs($user);
        foreach ([['document_id' => $base->id], ['folder_id' => $folder->id, 'selected_document_ids' => [$base->public_id]]] as $source) {
            $session = ChatSession::create(['user_id' => $user->id, 'title' => 'Bahas'] + $source);
            $response = $this->postJson(route('chat.store', $session), [
                'question' => 'Bandingkan', 'materials_present' => 1, 'material_ids' => [$extra->public_id],
            ]);
            $response->assertOk()->streamedContent();
            foreach ($source as $key => $value) $this->assertEquals($value, $session->fresh()->$key);
        }
        foreach (Http::recorded() as [$request]) {
            $prompt = $request['messages'][1]['content'][0]['text'];
            $this->assertStringContainsString('SUMBER_ASLI', $prompt);
            $this->assertStringContainsString('LAMPIRAN_TAMBAHAN', $prompt);
        }
        Http::assertSentCount(2);
        Queue::assertNothingPushed();
    }

    public function test_deleted_material_is_not_restored_and_foreign_session_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $material = $this->document($user);
        $session = ChatSession::create(['user_id' => $user->id, 'title' => 'Diskusi']);
        $session->messages()->create(['role' => 'user', 'content' => 'Bahas', 'metadata' => ['materials' => [['id' => $material->public_id]]]]);
        $material->delete();
        $this->assertTrue(app(ChatMaterials::class)->forSession($session)->isEmpty());
        $this->actingAs(User::factory()->create(['role' => 'mahasiswa']))
            ->postJson(route('chat.store', $session), ['question' => 'Bahas', 'materials_present' => 1])->assertForbidden();
        $this->assertSame(1, $session->messages()->count());
        Http::assertNothingSent();
    }

    public function test_upload_saves_private_readable_material_in_library(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $this->mock(DocumentTextExtractor::class)->shouldReceive('extract')->once()->andReturn('Materi hasil ekstraksi');
        $response = $this->actingAs($user)->postJson(route('chat.materials.store'), [
            'file' => UploadedFile::fake()->create('Ekosistem.pdf', 50, 'application/pdf'),
        ]);
        $response->assertCreated()->assertJsonPath('material.title', 'Ekosistem')->assertJsonPath('material.extension', 'PDF');
        $document = Document::firstOrFail();
        $this->assertSame($user->id, $document->user_id);
        $this->assertSame('Materi hasil ekstraksi', $document->extracted_text);
        $this->assertSame('processed', $document->status);
        Storage::disk('local')->assertExists($document->file_path);
        $this->getJson(route('chat.materials.index'))->assertJsonPath('items.0.id', $document->public_id);
        Http::assertNothingSent();
    }

    public function test_unreadable_and_invalid_uploads_do_not_leave_files_or_records(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $this->mock(DocumentTextExtractor::class)->shouldReceive('extract')->once()->andReturn('');
        $this->actingAs($user);
        foreach ([
            UploadedFile::fake()->create('scan.pdf', 50, 'application/pdf'),
            UploadedFile::fake()->create('script.php', 5, 'application/x-php'),
            UploadedFile::fake()->create('besar.pdf', 20481, 'application/pdf'),
        ] as $file) {
            $this->postJson(route('chat.materials.store'), ['file' => $file])->assertUnprocessable()->assertJsonValidationErrors('file');
        }
        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        Http::assertNothingSent();
    }
}
