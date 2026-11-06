<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Services\GeminiService;
use App\Services\NineRouterClient;
use GuzzleHttp\Psr7\PumpStream;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class SummaryGenerationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.ai.provider' => '9router', 'services.nine_router.api_key' => 'test-key',
            'services.nine_router.base_url' => 'https://router.test/v1',
            'services.nine_router.model' => 'ag/gemini-3.8-flash-medium', 'cache.default' => 'array',
        ]);
        Auth::shouldReceive('check')->andReturn(false);
        Http::preventStrayRequests();
    }

    public function test_summary_uses_one_streamed_request_and_preserves_long_markdown_without_json_escaping(): void
    {
        $body = "### Hukum fisika\n\nEnergi berpindah antarobjek. Rumus \\frac{1}{2}mv^2 memakai massa dan kecepatan.\n\n| Nilai | Arti |\n|---|---|\n| m | Massa |";
        $answer = "## Ringkasan lengkap\n".$body."\n\n## Poin penting\n- Energi dapat berpindah\n- Massa memengaruhi energi\n\n## Kesimpulan\nEnergi berkaitan dengan massa dan kecepatan.";
        $events = '';
        foreach (str_split($answer, 31) as $chunk) {
            $events .= 'data: '.json_encode(['choices' => [['delta' => ['content' => $chunk]]]])."\n\n";
        }
        $events .= 'data: {"choices":[{"delta":{},"finish_reason":"stop"}]}'."\n\n";
        Http::fake(['router.test/*' => Http::response($events, 200, ['Content-Type' => 'text/event-stream'])]);
        $document = new Document(['title' => 'Fisika', 'extracted_text' => 'Massa, kecepatan, dan energi.']);
        $result = app(GeminiService::class)->summarize($document);
        $this->assertSame($body, $result['full_summary']);
        $this->assertSame(['Energi dapat berpindah', 'Massa memengaruhi energi'], $result['key_points']);
        $this->assertSame('Energi berkaitan dengan massa dan kecepatan.', $result['conclusion']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['stream'] === true
            && $request['max_tokens'] === 8192 && $request['temperature'] === 0.7
            && $request['model'] === 'ag/gemini-3.8-flash-medium'
            && ! isset($request['response_format'])
            && $request->hasHeader('X-9Router-Token-Saver', 'off')
            && str_contains($request['messages'][1]['content'], 'Massa, kecepatan, dan energi.')
            && ! str_contains($request['messages'][1]['content'], '4500 kata'));
    }

    public function test_summary_stops_reading_as_soon_as_generation_is_finished(): void
    {
        $sent = false;
        $stream = new PumpStream(function () use (&$sent) {
            if ($sent) throw new RuntimeException('Unexpected read after completed summary');
            $sent = true;
            return 'data: '.json_encode(['choices' => [['delta' => ['content' => "## Ringkasan lengkap\nEnergi berpindah melalui panas."], 'finish_reason' => 'stop']]])."\n\n";
        });
        Http::fake(['router.test/*' => Http::response($stream, 200, ['Content-Type' => 'text/event-stream'])]);
        $this->assertSame("## Ringkasan lengkap\nEnergi berpindah melalui panas.", app(NineRouterClient::class)->completeSummary('Materi', 8192));
    }

    public function test_summary_removes_internal_reasoning_without_damaging_markdown(): void
    {
        $body = "### Energi\n\nEnergi berpindah melalui panas, sementara jumlah energi total tetap terjaga dalam sistem tertutup.\n\n| Bentuk | Contoh |\n|---|---|\n| Kinetik | Gerak |";
        Http::fake(['router.test/*' => Http::response('data: '.json_encode(['choices' => [[
            'delta' => ['content' => '<think>Catatan internal model</think>'.$body], 'finish_reason' => 'stop',
        ]]])."\n\n", 200, ['Content-Type' => 'text/event-stream'])]);

        $result = app(GeminiService::class)->summarize(new Document([
            'title' => 'Energi', 'extracted_text' => 'Energi total tetap terjaga dalam sistem tertutup.',
        ]));
        $this->assertSame($body, $result['full_summary']);
    }

    public function test_reasoning_without_an_answer_is_rejected(): void
    {
        Http::fake(['router.test/*' => Http::response('data: '.json_encode(['choices' => [[
            'delta' => ['content' => '<think>'.str_repeat('Catatan internal tanpa jawaban. ', 5).'</think>'], 'finish_reason' => 'stop',
        ]]])."\n\n", 200, ['Content-Type' => 'text/event-stream'])]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Jawaban AI belum berisi ringkasan lengkap');
        app(GeminiService::class)->summarize(new Document(['title' => 'Energi', 'extracted_text' => 'Materi energi.']));
    }

    public function test_truncated_summary_is_not_saved_as_a_complete_answer(): void
    {
        Http::fake(['router.test/*' => Http::response('data: '.json_encode(['choices' => [[
            'delta' => ['content' => "## Ringkasan lengkap\nJawaban terpotong"], 'finish_reason' => 'length',
        ]]])."\n\n", 200, ['Content-Type' => 'text/event-stream'])]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Ringkasan belum selesai');
        app(NineRouterClient::class)->completeSummary('Materi', 8192);
    }

    public function test_continuous_chunks_cannot_extend_the_total_summary_deadline(): void
    {
        config(['services.nine_router.timeout' => 5]);
        $client = new class extends NineRouterClient {
            public float $elapsed = 0;

            protected function monotonicSeconds(): float
            {
                return $this->elapsed;
            }
        };
        $reads = 0;
        $stream = new PumpStream(function () use ($client, &$reads) {
            $client->elapsed = ++$reads * 3;
            return 'data: '.json_encode(['choices' => [['delta' => ['content' => 'Pembahasan terus masuk. ']]]])."\n\n";
        });
        Http::fake(['router.test/*' => Http::response($stream, 200, ['Content-Type' => 'text/event-stream'])]);

        try {
            $client->completeSummary('Materi', 8192);
            $this->fail('Continuous streaming must respect the total deadline');
        } catch (RuntimeException $exception) {
            $this->assertSame('Batas waktu pembuatan ringkasan habis sebelum jawaban selesai', $exception->getMessage());
            $this->assertSame(2, $reads);
            $this->assertFalse($stream->isReadable());
        }
    }
}
