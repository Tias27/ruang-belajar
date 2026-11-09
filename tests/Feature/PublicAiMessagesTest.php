<?php

namespace Tests\Feature;

use App\Exceptions\AiServiceException;
use App\Models\ChatSession;
use App\Models\Document;
use App\Models\User;
use App\Services\ChatFileFormats;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicAiMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set([
            'database.default' => 'sqlite', 'database.connections.sqlite.url' => null,
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
    }

    public function test_saved_processing_notes_do_not_expose_technical_details_or_replace_source_text(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $text = 'Isi dokumen asli tetap dipakai sebagai konteks belajar.';
        $document = Document::create([
            'user_id' => $user->id, 'title' => 'Materi', 'original_name' => 'materi.docx',
            'file_name' => 'materi.docx', 'file_path' => 'documents/materi.docx', 'size' => 100,
            'extension' => 'docx', 'status' => 'processed', 'extracted_text' => $text,
            'processing_notes' => 'Gemini tetap dapat digunakan setelah extractor server dilengkapi. Model private-model di 9router.',
        ]);
        $this->actingAs($user)->get(route('documents.show', $document))->assertOk()
            ->assertSee(Document::TEXT_UNAVAILABLE_MESSAGE)->assertSee($text)
            ->assertDontSee('Gemini')->assertDontSee('9router')->assertDontSee('private-model')->assertDontSee('extractor server');
        $this->assertSame($text, $document->fresh()->extracted_text);
    }

    public function test_ai_exception_renders_neutral_json_and_html_even_with_debug_enabled(): void
    {
        config(['app.debug' => true]);
        Route::get('/test-ai-failure', function () {
            throw new AiServiceException('Gemini model private-model / API key private-key / 9router failure');
        });
        foreach ([$this->getJson('/test-ai-failure'), $this->get('/test-ai-failure')] as $response) {
            $response->assertStatus(503)->assertSee('AI belum bisa memproses permintaanmu')
                ->assertDontSee('Gemini')->assertDontSee('9router')->assertDontSee('private-model')
                ->assertDontSee('private-key')->assertDontSee('API key');
        }
    }

    public function test_provider_failures_in_chat_are_neutral(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $service = $this->mock(GeminiService::class);
        $service->shouldReceive('webMetadata')->andReturn([]);
        $service->shouldReceive('streamChat')->once()
            ->andThrow(new AiServiceException('Gemini private-model at 9router failed'));
        $response = $this->actingAs($user)->withHeader('Accept', 'text/event-stream')
            ->post(route('chat.general.store'), ['question' => 'Jelaskan laut']);
        $stream = $response->streamedContent();
        $this->assertStringContainsString('AI belum bisa menjawab', $stream);
        foreach (['Gemini', 'private-model', '9router'] as $name) $this->assertStringNotContainsString($name, $stream);
    }

    public function test_word_followup_converts_the_previous_answer_without_calling_ai_again(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $session = $user->chatSessions()->create(['title' => 'Fotosintesis']);
        $previousAnswer = "# Fotosintesis\n\nFotosintesis adalah proses tumbuhan membuat makanan dengan bantuan cahaya.";
        $session->messages()->create(['role' => 'assistant', 'content' => $previousAnswer]);

        $service = $this->mock(GeminiService::class);
        $service->shouldNotReceive('streamChat');
        $service->shouldReceive('webMetadata')->once()->andReturn([]);

        $response = $this->actingAs($user)->postJson(route('chat.store', $session), [
            'question' => 'Buatkan dalam Word',
        ])->assertOk();

        $this->assertStringContainsString('Baik, saya siapkan versi Word tentang Fotosintesis tadi sebagai file yang bisa diunduh.', $response->streamedContent());
        $this->assertStringNotContainsString('agar bisa langsung diunduh tanpa mengulang pembahasannya di chat', $response->streamedContent());
        $this->assertStringContainsString('<!-- FILE_DOCUMENT_FILE:fotosintesis.docx -->', $response->streamedContent());
        $fileMessage = $session->messages()->where('role', 'assistant')->latest('id')->firstOrFail();
        $this->assertStringContainsString("<!-- FILE_DOCUMENT_FILE:fotosintesis.docx -->\n".$previousAnswer, $fileMessage->content);
    }

    public function test_file_format_detection_handles_multiple_requested_assets(): void
    {
        $this->assertSame(
            ['html', 'css', 'js'],
            ChatFileFormats::requestedFormats('Buatkan halaman login dan pisahkan file HTML, CSS, dan JavaScript.')
        );
        $this->assertSame(['py'], ChatFileFormats::requestedFormats('Jadikan penjelasan tadi dalam format Python.'));
        $this->assertSame(['csv'], ChatFileFormats::requestedFormats('Buatkan dalam bentuk Excel.'));
    }

    public function test_generated_code_can_be_downloaded_as_a_source_file(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($user)->post(route('chat.export'), [
            'content' => 'print("Halo")',
            'format' => 'py',
            'filename' => 'contoh-python',
        ])->assertOk()
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'attachment; filename="contoh-python.py"')
            ->assertContent('print("Halo")');
    }

    public function test_admin_dashboard_uses_product_name_without_model_branding(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Asisten belajar AI')->assertDontSee('Gemini')->assertDontSee('1.5 Pro');
    }
}
