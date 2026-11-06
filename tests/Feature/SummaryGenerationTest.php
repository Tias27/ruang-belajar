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

    public function test_summary_targets_source_word_ratio_without_cutting_source_or_answer(): void
    {
        $source = str_repeat('Materi ', 1000);
        $body = trim(str_repeat('Penjelasan ', 440));
        $answer = "## Ringkasan lengkap\n{$body}\n## Poin penting\n- ".trim(str_repeat('Konsep ', 15))."\n## Kesimpulan\n".trim(str_repeat('Kesimpulan ', 10));
        Http::fake(['router.test/*' => Http::response('data: '.json_encode(['choices' => [[
            'delta' => ['content' => $answer], 'finish_reason' => 'stop',
        ]]])."\n\n", 200, ['Content-Type' => 'text/event-stream'])]);
        $document = new Document(['title' => 'Uji kepadatan', 'extracted_text' => $source]);
        $result = app(GeminiService::class)->summarize($document);
        $this->assertSame($source, $document->extracted_text);
        $this->assertSame($body, $result['full_summary']);
        $this->assertSame(['source_words' => 1000, 'min_words' => 450, 'max_words' => 480,
            'output_words' => 465, 'ratio_percent' => 46.5], $result['_length']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request['messages'][1]['content'], trim($source))
            && str_contains($request['messages'][1]['content'], 'target TOTAL jawaban 450-480 kata'));
    }

    public function test_folder_length_counts_only_selected_context(): void
    {
        $folder = new class extends \App\Models\DocumentFolder {
            public function documentsForPrompt(?array $selectedDocIds = null): \Illuminate\Support\Collection
            {
                return collect([
                    (new Document)->forceFill(['public_id' => 'chosen', 'title' => 'Biologi', 'extracted_text' => str_repeat('Sel ', 99)]),
                    (new Document)->forceFill(['public_id' => 'other', 'title' => 'Fisika', 'extracted_text' => str_repeat('Atom ', 899)]),
                ])->filter(fn ($document) => ! $selectedDocIds || in_array($document->public_id, $selectedDocIds, true));
            }
        };
        $folder->name = 'Kumpulan materi';
        Http::fake(['router.test/*' => Http::response('data: '.json_encode(['choices' => [[
            'delta' => ['content' => "## Ringkasan lengkap\nPembahasan sel dan fungsinya."], 'finish_reason' => 'stop',
        ]]])."\n\n", 200, ['Content-Type' => 'text/event-stream'])]);
        $result = app(GeminiService::class)->summarize($folder, ['chosen']);
        $this->assertSame(100, $result['_length']['source_words']);
        Http::assertSent(fn ($request) => str_contains($request['messages'][1]['content'], 'target TOTAL jawaban 45-48 kata')
            && ! str_contains($request['messages'][1]['content'], 'Atom'));
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
            $this->assertTrue($stream->eof());
        }
    }
}
