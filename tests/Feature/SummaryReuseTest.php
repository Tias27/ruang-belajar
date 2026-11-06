<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Summary;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\SummaryReuse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SummaryReuseTest extends TestCase
{
    use RefreshDatabase;

    private array $generationCalls = [];

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'session.driver' => 'array',
            'queue.default' => 'sync',
        ]);
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        $this->freezeTime();
        $this->mock(GeminiService::class)->shouldReceive('summarize')->andReturnUsing(function ($source, $ids = null) {
            $this->generationCalls[] = ['source' => $source->id, 'ids' => $ids];
            return [
                'full_summary' => 'Ringkasan hasil AI nomor '.count($this->generationCalls),
                'key_points' => ['Klorofil menangkap energi cahaya'],
                'conclusion' => 'Tumbuhan mengubah energi cahaya menjadi energi kimia',
            ];
        });
    }

    private function document(User $user, ?DocumentFolder $folder = null, string $title = 'Biologi'): Document
    {
        return Document::create([
            'user_id' => $user->id, 'folder_id' => $folder?->id, 'title' => $title,
            'original_name' => 'biologi.pdf', 'file_name' => 'fixture.pdf',
            'file_path' => 'documents/summary-reuse-fixture.pdf', 'mime_type' => 'application/pdf',
            'extension' => 'pdf', 'size' => 100, 'status' => 'processed',
            'extracted_text' => 'Klorofil menangkap energi cahaya dalam proses fotosintesis.',
        ]);
    }

    private function legacySummary(User $user, Document|DocumentFolder $source, array $attributes = []): Summary
    {
        return Summary::create(array_merge([
            'user_id' => $user->id,
            $source instanceof Document ? 'document_id' : 'folder_id' => $source->id,
            'short_summary' => 'Inti materi lama', 'full_summary' => 'Ringkasan lama yang sudah tersedia',
            'key_points' => ['Poin lama'], 'raw_response' => [], 'status' => 'completed',
        ], $attributes));
    }

    public function test_existing_document_summary_is_reused_without_new_row_or_ai_request(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $document = $this->document($user);
        $this->travel(1)->minutes();
        $summary = $this->legacySummary($user, $document);

        $this->actingAs($user)->post(route('summaries.store', $document))
            ->assertRedirect(route('summaries.show', $summary));

        $this->assertDatabaseCount('summaries', 1);
        $this->assertSame('Ringkasan lama yang sudah tersedia', $summary->fresh()->full_summary);
        $this->assertCount(0, $this->generationCalls);
        Http::assertNothingSent();
    }

    public function test_document_is_generated_once_and_regenerated_only_after_its_content_changes(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $document = $this->document($user);
        $this->actingAs($user)->post(route('summaries.store', $document))->assertRedirect();
        $first = Summary::firstOrFail();
        $this->assertSame('completed', $first->status);
        $this->assertNotEmpty($first->raw_response['_source_snapshot'] ?? null);
        $this->post(route('summaries.store', $document))->assertRedirect(route('summaries.show', $first));
        $this->assertCount(1, $this->generationCalls);

        // Frozen timestamps ensure invalidation follows source content, not only updated_at.
        $document->update(['extracted_text' => 'Respirasi sel melepaskan energi dari glukosa.']);
        $this->post(route('summaries.store', $document))->assertRedirect();
        $second = Summary::latest('id')->firstOrFail();
        $this->assertNotSame($first->id, $second->id);
        $this->post(route('summaries.store', $document))->assertRedirect(route('summaries.show', $second));
        $this->assertDatabaseCount('summaries', 2);
        $this->assertCount(2, $this->generationCalls);
    }

    public function test_existing_folder_summary_is_reused_until_a_file_is_added(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $folder = DocumentFolder::create(['user_id' => $user->id, 'name' => 'Sains']);
        $this->document($user, $folder);
        $this->travel(1)->minutes();
        $old = $this->legacySummary($user, $folder);
        $url = route('folders.summaries.store', $folder);

        $this->actingAs($user)->post($url)->assertRedirect(route('summaries.show', $old));
        $this->assertCount(0, $this->generationCalls);

        $this->travel(1)->minutes();
        $added = $this->document($user, $folder, 'Fisika');
        $this->post($url)->assertRedirect();
        $updated = Summary::latest('id')->firstOrFail();
        $this->assertNotSame($old->id, $updated->id);
        $this->assertSame('completed', $updated->status);
        $this->assertContains($added->public_id, $updated->raw_response['_source_snapshot']['document_ids']);
        $this->post($url)->assertRedirect(route('summaries.show', $updated));
        $this->assertDatabaseCount('summaries', 2);
        $this->assertCount(1, $this->generationCalls);
    }

    public function test_selected_files_are_matched_as_a_set_and_never_confused_with_other_selections(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $folder = DocumentFolder::create(['user_id' => $user->id, 'name' => 'Materi']);
        $a = $this->document($user, $folder, 'A');
        $b = $this->document($user, $folder, 'B');
        $c = $this->document($user, $folder, 'C');
        $url = route('folders.summaries.store', $folder);

        $this->actingAs($user)->post($url, ['document_ids' => [$a->public_id, $b->public_id]])->assertRedirect();
        $ab = Summary::latest('id')->firstOrFail();
        $this->post($url, ['document_ids' => [$b->public_id, $a->public_id]])->assertRedirect(route('summaries.show', $ab));
        $this->assertCount(1, $this->generationCalls);

        $this->post($url, ['document_ids' => [$c->public_id]])->assertRedirect();
        $onlyC = Summary::latest('id')->firstOrFail();
        $this->assertNotSame($ab->id, $onlyC->id);
        $this->post($url)->assertRedirect();
        $all = Summary::latest('id')->firstOrFail();
        $this->assertNotSame($onlyC->id, $all->id);
        $this->assertNotSame($ab->id, $all->id);
        $this->post($url, ['document_ids' => [$c->public_id, $a->public_id, $b->public_id]])
            ->assertRedirect(route('summaries.show', $all));
        $this->post($url, ['document_ids' => []])->assertRedirect(route('summaries.show', $all));
        $this->assertDatabaseCount('summaries', 3);
        $this->assertCount(3, $this->generationCalls);
    }

    public function test_unselected_additions_do_not_invalidate_selected_summary_but_full_folder_changes_do(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $folder = DocumentFolder::create(['user_id' => $user->id, 'name' => 'Materi']);
        $a = $this->document($user, $folder, 'A');
        $b = $this->document($user, $folder, 'B');
        $url = route('folders.summaries.store', $folder);
        $this->actingAs($user)->post($url, ['document_ids' => [$a->public_id]])->assertRedirect();
        $selected = Summary::latest('id')->firstOrFail();
        $this->document($user, $folder, 'C');
        $this->post($url, ['document_ids' => [$a->public_id]])->assertRedirect(route('summaries.show', $selected));
        $this->assertCount(1, $this->generationCalls);

        $this->post($url)->assertRedirect();
        $all = Summary::latest('id')->firstOrFail();
        $b->delete();
        $this->post($url)->assertRedirect();
        $afterRemoval = Summary::latest('id')->firstOrFail();
        $this->assertNotSame($all->id, $afterRemoval->id);
        $this->post($url)->assertRedirect(route('summaries.show', $afterRemoval));
        $this->assertDatabaseCount('summaries', 3);
        $this->assertCount(3, $this->generationCalls);
    }

    public function test_legacy_selected_summary_is_not_reused_for_whole_folder(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $folder = DocumentFolder::create(['user_id' => $user->id, 'name' => 'Materi']);
        $a = $this->document($user, $folder, 'A');
        $this->document($user, $folder, 'B');
        $this->travel(1)->minutes();
        $selected = $this->legacySummary($user, $folder, ['selected_document_ids' => [$a->public_id]]);
        $url = route('folders.summaries.store', $folder);
        $this->actingAs($user)->post($url, ['document_ids' => [$a->public_id]])->assertRedirect(route('summaries.show', $selected));
        $this->post($url)->assertRedirect();
        $this->assertNotSame($selected->id, Summary::latest('id')->firstOrFail()->id);
        $this->assertDatabaseCount('summaries', 2);
        $this->assertCount(1, $this->generationCalls);
    }

    public function test_unadopted_legacy_folder_results_are_invalidated_by_same_second_membership_changes(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $this->actingAs($user);
        foreach (['add', 'remove', 'move'] as $change) {
            $folder = DocumentFolder::create(['user_id' => $user->id, 'name' => 'Folder '.$change]);
            $this->document($user, $folder, 'Tetap');
            $changed = $this->document($user, $folder, 'Berubah');
            $legacy = $this->legacySummary($user, $folder);
            if ($change === 'add') $this->document($user, $folder, 'Tambahan');
            if ($change === 'remove') $changed->delete();
            if ($change === 'move') $changed->update(['folder_id' => null]);

            $this->post(route('folders.summaries.store', $folder))->assertRedirect();
            $new = Summary::latest('id')->firstOrFail();
            $this->assertNotSame($legacy->id, $new->id, $change.' must invalidate legacy result');
            $this->assertSame('completed', $new->status);
        }
        $this->assertDatabaseCount('summaries', 6);
        $this->assertCount(3, $this->generationCalls);
    }

    public function test_failed_or_blank_summary_can_be_retried_and_completed_result_is_then_reused(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $document = $this->document($user);
        $this->travel(1)->minutes();
        $this->legacySummary($user, $document, ['status' => 'failed']);
        $this->legacySummary($user, $document, ['full_summary' => '   ']);
        $this->actingAs($user)->post(route('summaries.store', $document))->assertRedirect();
        $completed = Summary::latest('id')->firstOrFail();
        $this->assertSame('completed', $completed->status);
        $this->assertSame('Ringkasan hasil AI nomor 1', $completed->full_summary);
        $this->post(route('summaries.store', $document))->assertRedirect(route('summaries.show', $completed));
        $this->assertDatabaseCount('summaries', 3);
        $this->assertCount(1, $this->generationCalls);
    }

    public function test_foreign_sources_and_invalid_folder_selections_never_generate_or_reuse(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $other = User::factory()->create(['role' => 'mahasiswa']);
        $folder = DocumentFolder::create(['user_id' => $user->id, 'name' => 'Milik sendiri']);
        $foreignFolder = DocumentFolder::create(['user_id' => $other->id, 'name' => 'Milik lain']);
        $owned = $this->document($user, $folder);
        $outsideFolder = $this->document($user);
        $foreign = $this->document($other, $foreignFolder);
        $this->actingAs($user);
        $this->post(route('summaries.store', $foreign))->assertForbidden();
        $this->post(route('folders.summaries.store', $foreignFolder))->assertForbidden();
        foreach ([['missing'], [$outsideFolder->public_id], [$foreign->public_id], [$owned->public_id, $owned->public_id], 'not-an-array'] as $selection) {
            $this->postJson(route('folders.summaries.store', $folder), ['document_ids' => $selection])->assertUnprocessable();
        }
        $this->assertDatabaseCount('summaries', 0);
        $this->assertCount(0, $this->generationCalls);
        Http::assertNothingSent();
    }

    public function test_recent_processing_result_is_shared_but_stale_processing_can_retry(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $document = $this->document($user);
        $processing = $this->legacySummary($user, $document, [
            'status' => 'processing',
            'raw_response' => ['_source_snapshot' => app(SummaryReuse::class)->snapshot($document)],
        ]);
        $url = route('summaries.store', $document);
        $this->actingAs($user)->post($url)->assertRedirect(route('summaries.show', $processing));
        $this->assertDatabaseCount('summaries', 1);
        $this->assertCount(0, $this->generationCalls);

        $this->travel(7)->minutes();
        $this->post($url)->assertRedirect();
        $retry = Summary::latest('id')->firstOrFail();
        $this->assertNotSame($processing->id, $retry->id);
        $this->assertSame('completed', $retry->status);
        $this->post($url)->assertRedirect(route('summaries.show', $retry));
        $this->assertDatabaseCount('summaries', 2);
        $this->assertCount(1, $this->generationCalls);
    }

    public function test_source_changes_during_generation_do_not_make_the_old_result_reusable(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $folder = DocumentFolder::create(['user_id' => $user->id, 'name' => 'Berubah saat proses']);
        $this->document($user, $folder);
        $calls = 0;
        $this->mock(GeminiService::class)->shouldReceive('summarize')->andReturnUsing(function () use (&$calls, $user, $folder) {
            if (++$calls === 1) $this->document($user, $folder, 'Ditambahkan saat AI membaca');
            return ['full_summary' => 'Hasil pembacaan '.$calls, 'key_points' => []];
        });
        $url = route('folders.summaries.store', $folder);
        $this->actingAs($user)->post($url)->assertRedirect();
        $first = Summary::latest('id')->firstOrFail();
        $this->post($url)->assertRedirect();
        $second = Summary::latest('id')->firstOrFail();
        $this->assertNotSame($first->id, $second->id);
        $this->assertSame('Hasil pembacaan 2', $second->full_summary);
        $this->post($url)->assertRedirect(route('summaries.show', $second));
        $this->assertSame(2, $calls);
        $this->assertDatabaseCount('summaries', 2);
    }

    public function test_empty_ai_result_is_failed_and_does_not_poison_later_reuse(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $document = $this->document($user);
        $calls = 0;
        $this->mock(GeminiService::class)->shouldReceive('summarize')->andReturnUsing(function () use (&$calls) {
            return ++$calls === 1 ? [] : ['full_summary' => 'Ringkasan hasil percobaan ulang', 'key_points' => []];
        });
        $url = route('summaries.store', $document);
        $this->actingAs($user)->post($url)->assertRedirect();
        $failed = Summary::firstOrFail();
        $this->assertSame('failed', $failed->status);
        $this->post($url)->assertRedirect();
        $success = Summary::latest('id')->firstOrFail();
        $this->assertNotSame($failed->id, $success->id);
        $this->assertSame('completed', $success->status);
        $this->post($url)->assertRedirect(route('summaries.show', $success));
        $this->assertSame(2, $calls);
        $this->assertDatabaseCount('summaries', 2);
    }

    public function test_legacy_null_raw_response_is_adopted_without_erasing_summary_content(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $document = $this->document($user);
        $this->travel(1)->minutes();
        $old = $this->legacySummary($user, $document, ['raw_response' => null]);
        $this->actingAs($user)->post(route('summaries.store', $document))
            ->assertRedirect(route('summaries.show', $old));
        $this->assertSame('Ringkasan lama yang sudah tersedia', $old->fresh()->full_summary);
        $this->assertNotEmpty($old->fresh()->raw_response['_source_snapshot']);
        $this->assertCount(0, $this->generationCalls);
    }
}
