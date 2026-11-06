<?php

namespace Tests\Feature;

use App\Jobs\ConsolidateAiMemoryJob;
use App\Models\ChatSession;
use App\Models\Document;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\MaterialPreparationCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiResponsivenessTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'queue.connections.ai_memory.connection' => 'sqlite',
            'cache.default' => 'array',
            'session.driver' => 'array',
        ]);
        return $app;
    }

    public function test_chat_stream_saves_answer_and_queues_memory_without_second_provider_call(): void
    {
        Queue::fake();
        Http::preventStrayRequests();
        config([
            'services.ai.provider' => '9router',
            'services.nine_router.api_key' => 'test-key',
            'services.nine_router.base_url' => 'https://router.test/v1',
            'services.nine_router.model' => 'ag/gemini-3.8-flash-medium',
        ]);
        Http::fake(['router.test/*' => Http::response(
            'data: {"choices":[{"delta":{"content":"Pertukaran "}}]}'."\n\n"
            .'data: {"choices":[{"delta":{"content":"gas"},"finish_reason":"stop"}]}'."\n\n"
            .'data: [DONE]', 200, ['Content-Type' => 'text/event-stream']
        )]);
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $document = Document::create([
            'user_id' => $user->id, 'title' => 'Pernapasan',
            'original_name' => 'test.pdf', 'file_name' => 'test.pdf',
            'file_path' => 'documents/test.pdf', 'mime_type' => 'application/pdf',
            'extension' => 'pdf', 'size' => 100, 'status' => 'processed',
            'extracted_text' => 'Alveolus menjadi tempat pertukaran gas.',
        ]);
        $session = ChatSession::create(['user_id' => $user->id, 'document_id' => $document->id, 'title' => 'Uji']);
        $response = $this->actingAs($user)->withHeader('Accept', 'text/event-stream')
            ->post(route('chat.store', $session), ['question' => 'Fungsi alveolus?']);
        $response->assertOk()->assertHeader('X-Accel-Buffering', 'no');
        $stream = $response->streamedContent();
        $this->assertStringContainsString('"chunk":"Pertukaran "', $stream);
        $this->assertStringContainsString('"chunk":"gas"', $stream);
        $this->assertStringContainsString('"done":true', $stream);
        $this->assertDatabaseHas('chat_messages', ['chat_session_id' => $session->id, 'role' => 'assistant', 'content' => 'Pertukaran gas']);
        Queue::assertPushed(ConsolidateAiMemoryJob::class, fn ($job) => $job->connection === 'ai_memory' && $job->queue === 'ai-memory');
        Http::assertSentCount(1);
    }

    public function test_cache_reuses_preparation_and_invalidates_content_and_limit_changes(): void
    {
        $cache = app(MaterialPreparationCache::class);
        $calls = 0;
        $prepare = function () use (&$calls) { return ++$calls; };
        $this->assertSame(1, $cache->remember('compact:100', 'materi', $prepare));
        $this->assertSame(1, $cache->remember('compact:100', 'materi', $prepare));
        $this->assertSame(2, $cache->remember('compact:100', 'materi berubah', $prepare));
        $this->assertSame(3, $cache->remember('compact:200', 'materi', $prepare));
        $this->travel(7)->hours();
        $this->assertSame(4, $cache->remember('compact:100', 'materi', $prepare));
    }

    public function test_general_chat_needs_no_material_and_reuses_session_with_history(): void
    {
        $this->withoutVite();
        Queue::fake();
        Http::preventStrayRequests();
        config(['services.ai.provider' => '9router', 'services.nine_router.api_key' => 'test',
            'services.nine_router.base_url' => 'https://router.test/v1', 'services.nine_router.model' => 'ag/gemini-3.8-flash-medium']);
        Http::fake(['router.test/*' => fn () => Http::response('data: {"choices":[{"delta":{"content":"Ayo diskusi"},"finish_reason":"stop"}]}'."\n\n")]);
        $this->get(route('chat.general'))->assertRedirect(route('login'));
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $this->actingAs($user)->get(route('chat.general'))->assertOk()->assertSee('Mau ngobrolin apa?');
        $this->assertDatabaseCount('chat_sessions', 0);
        $this->postJson(route('chat.general.store'), ['question' => ''])->assertUnprocessable();
        $this->assertDatabaseCount('chat_sessions', 0);
        $first = $this->withHeader('Accept', 'text/event-stream')->post(route('chat.general.store'), ['question' => 'Ide akhir pekan?']);
        $first->assertOk();
        $this->assertStringContainsString('"store_url":', $first->streamedContent());
        $session = ChatSession::firstOrFail();
        $this->assertNull($session->document_id);
        $this->assertNull($session->folder_id);
        $next = $this->post(route('chat.store', $session), ['question' => 'Yang bisa di rumah?']);
        $this->assertStringContainsString('Ayo diskusi', $next->streamedContent());
        $this->assertDatabaseCount('chat_sessions', 1);
        $this->assertSame(4, $session->messages()->count());
        Http::assertSent(fn ($request) => str_contains($request['messages'][1]['content'][0]['text'], 'Ide akhir pekan?')
            && str_contains($request['messages'][1]['content'][0]['text'], 'Yang bisa di rumah?'));
        Queue::assertNothingPushed();
        $this->get(route('chat.show', $session))->assertOk()->assertSee('Chat bebas');
        $this->get(route('chat.index'))->assertOk()->assertSee('Chat bebas');
        $this->actingAs(User::factory()->create(['role' => 'mahasiswa']))
            ->get(route('chat.show', $session))->assertForbidden();
        $this->post(route('chat.store', $session), ['question' => 'Uji'])->assertForbidden();
    }

    public function test_memory_lock_defers_overlapping_job_instead_of_overwriting_memory(): void
    {
        $user = new User;
        $user->id = 9;
        $document = new Document;
        $document->id = 10;
        $job = new ConsolidateAiMemoryJob($document, $user, []);
        $queueJob = \Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
        $queueJob->shouldReceive('release')->once()->with(10);
        $job->setJob($queueJob);
        $middleware = $job->middleware()[0];
        $lock = Cache::lock($middleware->getLockKey($job), 650);
        $this->assertTrue($lock->get());
        try {
            $middleware->handle($job, fn () => $this->fail('Concurrent memory write must wait'));
        } finally {
            $lock->release();
        }
        $this->assertGreaterThan($job->timeout, config('queue.connections.ai_memory.retry_after'));
    }

    public function test_generation_context_cache_preserves_original_output(): void
    {
        config(['services.ai.provider' => '9router']);
        $service = app(GeminiService::class);
        $method = new \ReflectionMethod($service, 'generationContext');
        $document = new Document(['extracted_text' => str_repeat('Materi lengkap. ', 1500)]);
        foreach (['summary', 'quiz', 'flashcard'] as $task) {
            $this->assertSame(trim($document->extracted_text), $method->invoke($service, $document, $task));
            $this->assertSame(trim($document->extracted_text), $method->invoke($service, $document, $task));
        }
        $document->extracted_text = 'Isi baru';
        $this->assertSame('Isi baru', $method->invoke($service, $document, 'summary'));
    }
}
