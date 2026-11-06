<?php

namespace Tests\Feature;

use App\Jobs\GenerateSummaryJob;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Summary;
use App\Models\User;
use App\Services\AutomaticSummary;
use App\Services\DocumentTextExtractor;
use App\Services\GeminiService;
use App\Services\SummaryReuse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AutomaticSummaryTest extends TestCase
{
    use RefreshDatabase;

    private string $fixtureDirectory;
    private string $originalStorage;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set([
            'database.default' => 'sqlite', 'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'queue.connections.ai_summaries.connection' => 'sqlite',
            'queue.connections.ai_summaries.after_commit' => false,
            'cache.default' => 'array', 'session.driver' => 'array',
        ]);
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        $this->originalStorage = storage_path();
        $this->fixtureDirectory = storage_path('framework/testing/automatic-summary-'.Str::uuid());
        File::ensureDirectoryExists($this->fixtureDirectory.'/framework');
        app()->useStoragePath($this->fixtureDirectory);
    }

    protected function tearDown(): void
    {
        if (isset($this->fixtureDirectory)) {
            app()->useStoragePath($this->originalStorage);
            File::deleteDirectory($this->fixtureDirectory);
        }
        parent::tearDown();
    }

    private function document(User $user, array $attributes = []): Document
    {
        return Document::create(array_merge([
            'user_id' => $user->id, 'title' => 'Ekosistem', 'original_name' => 'materi.pdf',
            'file_name' => 'materi.pdf', 'file_path' => 'documents/materi.pdf',
            'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size' => 100,
            'status' => 'processed', 'extracted_text' => 'Produsen mengubah energi cahaya menjadi energi kimia.',
        ], $attributes));
    }

    public function test_upload_paths_queue_summaries_without_waiting_for_ai(): void
    {
        Queue::fake();
        Storage::fake('local');
        $this->mock(DocumentTextExtractor::class)->shouldReceive('extract')->andReturn('Materi ekosistem yang berhasil dibaca.');
        $this->mock(GeminiService::class)->shouldNotReceive('summarize');
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $this->actingAs($user);
        $this->post(route('documents.store'), ['files' => [UploadedFile::fake()->create('satuan.pdf', 1, 'application/pdf')]])->assertRedirect();
        $folder = DocumentFolder::create(['user_id' => $user->id, 'name' => 'Folder']);
        $this->post(route('folders.documents.store', $folder), ['files' => [UploadedFile::fake()->create('folder.pdf', 1, 'application/pdf')]])->assertRedirect();
        $this->post(route('documents.store'), [
            'upload_mode' => 'folder', 'folder_name' => 'Folder baru',
            'files' => [UploadedFile::fake()->create('gabungan.pdf', 1, 'application/pdf')],
        ])->assertRedirect();
        $this->postJson(route('chat.materials.store'), ['file' => UploadedFile::fake()->create('chat.pdf', 1, 'application/pdf')])->assertCreated();

        $this->assertDatabaseCount('summaries', 4);
        $this->assertSame(4, Summary::where('status', 'queued')->whereNotNull('document_id')->whereNull('folder_id')->count());
        Queue::assertPushed(GenerateSummaryJob::class, 4);
        Queue::assertPushed(GenerateSummaryJob::class, fn ($job) => $job->connection === 'ai_summaries' && $job->queue === 'ai-summaries');
        Http::assertNothingSent();
    }

    public function test_unreadable_files_skip_generation_and_repeated_scheduling_does_not_duplicate(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $unreadable = $this->document($user, ['status' => 'uploaded', 'extracted_text' => '']);
        app(AutomaticSummary::class)->schedule($unreadable);
        $this->assertDatabaseCount('summaries', 0);
        $ready = $this->document($user);
        app(AutomaticSummary::class)->schedule($ready);
        $this->travel(10)->minutes();
        app(AutomaticSummary::class)->schedule($ready);
        $this->assertDatabaseCount('summaries', 1);
        Queue::assertPushed(GenerateSummaryJob::class, 1);
    }

    public function test_database_worker_completes_once_and_click_reuses_saved_result(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $document = $this->document($user);
        $originalSource = $document->fresh()->getAttributes();
        $this->mock(GeminiService::class)->shouldReceive('summarize')->once()->andReturn([
            'full_summary' => 'Ringkasan lengkap materi ekosistem.', 'key_points' => ['Produsen menyimpan energi'],
        ]);
        app(AutomaticSummary::class)->schedule($document);
        $summary = Summary::firstOrFail();
        $this->assertSame(1, Queue::connection('ai_summaries')->size('ai-summaries'));
        $this->assertSame(0, Artisan::call('ai:summary-worker', ['--once' => true]));
        $this->assertSame(0, Queue::connection('ai_summaries')->size('ai-summaries'));
        $this->assertSame('completed', $summary->fresh()->status);
        $this->assertSame($originalSource, $document->fresh()->getAttributes());
        $this->actingAs($user)->postJson(route('summaries.store', $document))
            ->assertOk()->assertJsonPath('url', route('summaries.show', $summary))->assertJsonPath('status', 'completed');
        $this->assertDatabaseCount('summaries', 1);
        $this->assertGreaterThan((new GenerateSummaryJob($summary->id))->timeout, config('queue.connections.ai_summaries.retry_after'));
    }

    public function test_user_click_can_claim_queued_work_and_later_worker_skips_duplicate(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $document = $this->document($user);
        app(AutomaticSummary::class)->schedule($document);
        $summary = Summary::firstOrFail();
        $this->mock(GeminiService::class)->shouldReceive('summarize')->once()->andReturn(['full_summary' => 'Ringkasan sudah selesai']);
        $this->actingAs($user)->postJson(route('summaries.store', $document))->assertOk()->assertJsonPath('status', 'completed');
        app()->call([new GenerateSummaryJob($summary->id), 'handle']);
        $this->assertDatabaseCount('summaries', 1);
    }

    public function test_click_while_worker_runs_polls_same_result_without_second_call(): void
    {
        Queue::fake();
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $document = $this->document($user);
        app(AutomaticSummary::class)->schedule($document);
        $summary = Summary::firstOrFail();
        $this->actingAs($user);
        $this->mock(GeminiService::class)->shouldReceive('summarize')->once()->andReturnUsing(function () use ($document, $summary) {
            $this->postJson(route('summaries.store', $document))->assertStatus(202)->assertJsonPath('status', 'processing');
            app()->call([new GenerateSummaryJob($summary->id), 'handle']);
            $this->getJson(route('summaries.status', $summary))->assertStatus(202)->assertJsonPath('status', 'processing');
            return ['full_summary' => 'Hasil worker yang sama'];
        });
        app()->call([new GenerateSummaryJob($summary->id), 'handle']);
        $this->getJson(route('summaries.status', $summary))->assertOk()->assertJsonPath('status', 'completed');
        $this->assertDatabaseCount('summaries', 1);
    }

    public function test_failures_can_retry_and_status_never_exposes_internal_error(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $document = $this->document($user);
        app(AutomaticSummary::class)->schedule($document);
        $summary = Summary::firstOrFail();
        $attempt = 0;
        $this->mock(GeminiService::class)->shouldReceive('summarize')->twice()->andReturnUsing(function () use (&$attempt) {
            if (++$attempt === 1) throw new \RuntimeException('Private provider details');
            return ['full_summary' => 'Ringkasan percobaan kedua'];
        });
        app()->call([new GenerateSummaryJob($summary->id), 'handle']);
        $this->actingAs($user)->getJson(route('summaries.status', $summary))
            ->assertOk()->assertJsonPath('status', 'failed')->assertDontSee('Private provider details');
        $this->get(route('summaries.show', $summary))->assertOk()->assertSee('Coba lagi')->assertDontSee('Private provider details');
        $this->postJson(route('summaries.store', $document))->assertOk()->assertJsonPath('status', 'completed');
        $this->actingAs(User::factory()->create(['role' => 'mahasiswa']))
            ->getJson(route('summaries.status', $summary))->assertForbidden();
    }

    public function test_deleted_or_changed_sources_are_not_sent_to_ai_from_old_jobs(): void
    {
        Queue::fake();
        $this->mock(GeminiService::class)->shouldNotReceive('summarize');
        $user = User::factory()->create();
        foreach (['changed', 'deleted'] as $action) {
            $document = $this->document($user);
            app(AutomaticSummary::class)->schedule($document);
            $summary = Summary::latest('id')->firstOrFail();
            if ($action === 'changed') $document->update(['extracted_text' => 'Isi baru']);
            else $document->delete();
            app()->call([new GenerateSummaryJob($summary->id), 'handle']);
            if ($action === 'changed') $this->assertSame('failed', $summary->fresh()->status);
            else $this->assertNull($summary->fresh());
        }
    }

    public function test_queue_outage_does_not_fail_upload_and_stalled_jobs_become_retryable(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $document = $this->document($user);
        $queue = \Mockery::mock(\Illuminate\Contracts\Queue\Queue::class);
        $queue->shouldReceive('push')->once()->andThrow(new \RuntimeException('Queue unavailable'));
        Queue::shouldReceive('connection')->with('ai_summaries')->andReturn($queue);
        app(AutomaticSummary::class)->schedule($document);
        $this->assertSame('failed', Summary::firstOrFail()->status);
        [$summary] = app(SummaryReuse::class)->resolve($document);
        $this->travel(7)->minutes();
        $this->actingAs($user)->getJson(route('summaries.status', $summary))->assertOk()->assertJsonPath('status', 'failed');
    }
}
