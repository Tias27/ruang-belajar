<?php

namespace Tests\Feature;

use App\Services\GeminiService;
use App\Services\NineRouterClient;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class NineRouterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.ai.provider' => '9router',
            'services.nine_router.api_key' => 'test-router-key',
            'services.nine_router.base_url' => 'https://router.test/v1/',
            'services.nine_router.model' => 'ag/gemini-3.8-flash-medium',
        ]);
        Http::preventStrayRequests();
    }

    public function test_generation_tasks_use_router_and_json_mode_without_gemini_fallback(): void
    {
        Http::fake(['router.test/*' => Http::response(['choices' => [['message' => ['content' => '{"result":"ok"}']]]])]);
        $method = new ReflectionMethod(GeminiService::class, 'sendText');
        foreach (['summary', 'quiz', 'flashcard', 'default'] as $task) {
            $this->assertSame('{"result":"ok"}', $method->invoke(app(GeminiService::class), 'Materi uji', 2000, 'application/json', $task));
        }
        Http::assertSentCount(4);
        Http::assertSent(fn ($request) => $request->url() === 'https://router.test/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-router-key')
            && $request->hasHeader('X-9Router-Token-Saver', 'off')
            && $request['model'] === 'ag/gemini-3.8-flash-medium'
            && $request['response_format']['type'] === 'json_object'
            && $request['stream'] === false);
    }

    public function test_image_input_and_stream_events_preserve_text_without_reasoning(): void
    {
        $events = 'data:'.json_encode(['choices' => [['delta' => ['reasoning_content' => 'private']]]])."\r\n\r\n"
            .'data: '.json_encode(['choices' => [['delta' => ['content' => 'Halo ']]]])."\n\n"
            .'data: '.json_encode(['choices' => [['delta' => ['content' => 'pelajar'], 'finish_reason' => 'stop']]])."\n\n"
            .'data: [DONE]';
        Http::fake(['router.test/*' => Http::response($events, 200, ['Content-Type' => 'text/event-stream'])]);
        $parts = [['text' => 'Apa isi gambar?'], ['inlineData' => ['mimeType' => 'image/png', 'data' => 'aW1hZ2U=']]];
        $this->assertSame('Halo pelajar', implode('', iterator_to_array(app(NineRouterClient::class)->stream($parts, 1100))));
        Http::assertSent(fn ($request) => $request['stream'] === true
            && $request->hasHeader('X-9Router-Token-Saver', 'off')
            && $request['messages'][1]['content'][1]['image_url']['url'] === 'data:image/png;base64,aW1hZ2U=');
    }

    public function test_router_error_does_not_leak_response_or_call_other_provider(): void
    {
        Http::fake(['router.test/*' => Http::response('test-router-key private document', 401)]);
        try {
            (new ReflectionMethod(GeminiService::class, 'sendText'))->invoke(app(GeminiService::class), 'Materi privat', 1000);
            $this->fail('Expected router failure');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('HTTP 401', $exception->getMessage());
            $this->assertStringNotContainsString('test-router-key', $exception->getMessage());
            $this->assertStringNotContainsString('private document', $exception->getMessage());
        }
        Http::assertSentCount(1);
    }

    public function test_interrupted_stream_is_not_reported_as_success(): void
    {
        Http::fake(['router.test/*' => Http::response('data: {"choices":[{"delta":{"content":"Parsial"}}]}'."\n\n")]);
        $this->expectException(RuntimeException::class);
        iterator_to_array(app(NineRouterClient::class)->stream('Uji', 1100));
    }

    public function test_finished_answer_does_not_wait_for_more_network_data(): void
    {
        $sent = false;
        $body = new \GuzzleHttp\Psr7\PumpStream(function () use (&$sent) {
            if ($sent) {
                throw new RuntimeException('Read attempted after finish_reason');
            }
            $sent = true;
            return 'data: {"choices":[{"delta":{"content":"Selesai"},"finish_reason":"stop"}]}'."\n\n";
        });
        Http::fake(['router.test/*' => Http::response($body, 200, ['Content-Type' => 'text/event-stream'])]);
        $this->assertSame('Selesai', implode('', iterator_to_array(app(NineRouterClient::class)->stream('Uji', 1100))));
    }

    public function test_chat_service_routes_stream_and_document_context_to_router(): void
    {
        Auth::shouldReceive('check')->andReturn(false);
        Http::fake(['router.test/*' => Http::response('data: {"choices":[{"delta":{"content":"Jawaban"},"finish_reason":"stop"}]}'."\n\n".'data: [DONE]')]);
        $document = new Document(['title' => 'Materi uji', 'extracted_text' => 'Alveolus untuk pertukaran gas']);
        $this->assertSame('Jawaban', implode('', iterator_to_array(app(GeminiService::class)->streamChat($document, 'Apa fungsi alveolus?'))));
        Http::assertSent(fn ($request) => $request->url() === 'https://router.test/v1/chat/completions'
            && str_contains($request['messages'][1]['content'][0]['text'], 'Alveolus untuk pertukaran gas'));
        Http::assertSentCount(1);
    }

    public function test_general_and_material_chat_send_complete_guidance_with_room_for_detailed_answers(): void
    {
        Auth::shouldReceive('check')->andReturn(false);
        Http::fake(['router.test/*' => fn ($request) => $request['stream']
            ? Http::response('data: {"choices":[{"delta":{"content":"Penjelasan"},"finish_reason":"stop"}]}'."\n\n")
            : Http::response(['choices' => [['message' => ['content' => 'Penjelasan']]]])]);
        $document = new Document(['title' => 'Biologi', 'extracted_text' => 'Alveolus tempat pertukaran gas']);
        $history = [['role' => 'user', 'content' => 'Tadi membahas paru-paru']];
        foreach ([null, $document] as $source) {
            $service = app(GeminiService::class);
            $this->assertSame('Penjelasan', $service->chat($source, 'Jelaskan dengan contoh', $history));
            $this->assertSame('Penjelasan', implode('', iterator_to_array($service->streamChat($source, 'Jelaskan dengan contoh', $history))));
        }
        $requests = Http::recorded()->map(fn ($record) => $record[0]);
        $this->assertCount(4, $requests);
        foreach ($requests as $index => $request) {
            $prompt = $request['messages'][1]['content'][0]['text'];
            $this->assertSame(8192, $request['max_tokens']);
            $this->assertTrue($request->hasHeader('X-9Router-Token-Saver', 'off'));
            $this->assertSame('ag/gemini-3.8-flash-medium', $request['model']);
            $this->assertStringContainsString('tanpa target jumlah kata atau susunan wajib', $request['messages'][0]['content']);
            $this->assertStringContainsString('tanpa patokan jumlah kata, kalimat, atau format wajib', $prompt);
            $this->assertStringContainsString('Mulai dari pengertian sederhana', $prompt);
            $this->assertStringContainsString('terangkan apa yang terjadi', $prompt);
            $this->assertStringNotContainsString('300-700', $request['messages'][0]['content'].$prompt);
            $this->assertStringNotContainsString('Susun jawaban seperti tutor belajar:', $prompt);
            $this->assertStringContainsString('Tadi membahas paru-paru', $prompt);
            $this->assertStringContainsString('Pertanyaan: Jelaskan dengan contoh', $prompt);
            if ($index < 2) {
                $this->assertStringNotContainsString('Gunakan materi sebagai acuan utama', $prompt);
            } else {
                $this->assertStringContainsString('Alveolus tempat pertukaran gas', $prompt);
            }
        }
    }

    public function test_visual_capable_chat_selects_formats_automatically_without_changing_model_or_streaming(): void
    {
        Auth::shouldReceive('check')->andReturn(false);
        Http::fake(['router.test/*' => fn ($request) => $request['stream']
            ? Http::response('data: {"choices":[{"delta":{"content":"Penjelasan"},"finish_reason":"stop"}]}'."\n\n")
            : Http::response(['choices' => [['message' => ['content' => 'Penjelasan']]]])]);
        $material = new Document(['title' => 'Siklus air', 'extracted_text' => 'Air menguap lalu mengembun.']);
        foreach ([null, $material] as $source) {
            $service = app(GeminiService::class);
            $this->assertSame('Penjelasan', $service->chat($source, 'Jelaskan siklus air', richResponses: true));
            $this->assertSame('Penjelasan', implode('', iterator_to_array($service->streamChat($source, 'Jelaskan siklus air', richResponses: true))));
        }

        Http::assertSentCount(4);
        foreach (Http::recorded() as [$request]) {
            $prompt = $request['messages'][1]['content'][0]['text'];
            $this->assertStringContainsString('Pilih sendiri bentuk penjelasan', $prompt);
            $this->assertStringContainsString('Jangan memaksakan tabel, diagram, atau grafik pada setiap jawaban', $prompt);
            $this->assertStringContainsString('jawaban singkat, teks saja', $prompt);
            $this->assertStringContainsString('blok kode berlabel mermaid', $prompt);
            $this->assertStringContainsString('blok kode berlabel chart berisi JSON valid', $prompt);
            $this->assertStringContainsString('"datasets":[{"label":"Nama seri","data":[10,20]}]', $prompt);
            $this->assertStringContainsString('labels maksimal 50 dan datasets maksimal 6', $prompt);
            $this->assertStringContainsString('Jangan mengarang statistik', $prompt);
            $this->assertStringContainsString('contoh hipotetis', $prompt);
            $this->assertStringContainsString('LaTeX dalam $...$ atau \\(...\\)', $prompt);
            $this->assertStringNotContainsString('tulis rumus sebagai teks', $prompt);
            $this->assertStringContainsString('Jangan mengaku mencari web', $prompt);
            $this->assertSame(8192, $request['max_tokens']);
            $this->assertSame('ag/gemini-3.8-flash-medium', $request['model']);
            $this->assertTrue($request->hasHeader('X-9Router-Token-Saver', 'off'));
        }
    }

    public function test_chat_without_visual_renderer_keeps_plain_format_guidance(): void
    {
        Auth::shouldReceive('check')->andReturn(false);
        Http::fake(['router.test/*' => Http::response(['choices' => [['message' => ['content' => 'Penjelasan']]]])]);
        $material = new Document(['title' => 'Belajar bareng', 'extracted_text' => 'Materi diskusi']);
        app(GeminiService::class)->chat($material, 'Jelaskan');
        Http::assertSent(function ($request) {
            $prompt = $request['messages'][1]['content'][0]['text'];

            return str_contains($prompt, 'tulis rumus sebagai teks')
                && !str_contains($prompt, 'blok kode berlabel mermaid')
                && !str_contains($prompt, 'blok kode berlabel chart');
        });
    }

    public function test_streaming_and_nonstreaming_chat_preserve_visual_blocks_tables_and_code_spacing(): void
    {
        $reply = <<<'MARKDOWN'
Bandingkan hasilnya:

| Percobaan | Nilai |
| --- | ---: |
| A | 10 |

```mermaid
flowchart TD
    A["Air"] --> B["Uap"]
```

```chart
{"type":"bar","title":"Contoh hipotetis","labels":["A"],"datasets":[{"label":"Nilai","data":[10]}]}
```

Rumus $v = \frac{s}{t}$.

```python
def contoh():
    value = "a|b"



    return value
```
MARKDOWN;
        Http::fake(['router.test/*' => fn ($request) => $request['stream']
            ? Http::response('data: '.json_encode(['choices' => [['delta' => ['content' => $reply], 'finish_reason' => 'stop']]])."\n\n")
            : Http::response(['choices' => [['message' => ['content' => $reply]]]])]);

        $service = app(GeminiService::class);
        $this->assertSame($reply, $service->chat(null, 'Jelaskan', richResponses: true));
        $this->assertSame($reply, implode('', iterator_to_array($service->streamChat(null, 'Jelaskan', richResponses: true))));
        Http::assertSentCount(2);
    }
}
