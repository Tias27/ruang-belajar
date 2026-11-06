<?php

// Sends only synthetic material; never reads user documents or prints credentials.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$client = app(\App\Services\NineRouterClient::class);
$request = new ReflectionMethod($client, 'request');
$buildPrompt = new ReflectionMethod(\App\Services\GeminiService::class, 'buildPrompt');

foreach ([1, 2] as $trial) {
    $started = microtime(true);
    $document = new \App\Models\Document([
        'title' => 'Materi uji sintetis',
        'extracted_text' => 'Alveolus adalah tempat pertukaran gas oksigen dan karbon dioksida di paru-paru.',
    ]);
    $prompt = $buildPrompt->invoke(app(\App\Services\GeminiService::class), $document,
        'Jelaskan fungsi alveolus dalam tiga kalimat singkat.', 'Apa fungsi alveolus?', 'chat');
    $prepared = microtime(true);
    $body = null;
    try {
        $response = $request->invoke($client, [['text' => $prompt]], 1100, false, 'chat', true);
        $headers = microtime(true);
        $body = $response->toPsrResponse()->getBody();
        $firstEvent = $firstText = $lastText = null;
        $events = $textEvents = 0;
        while (!$body->eof()) {
            $line = trim(\GuzzleHttp\Psr7\Utils::readLine($body));
            if (!str_starts_with($line, 'data:')) continue;
            $now = microtime(true);
            $firstEvent ??= $now;
            $data = trim(substr($line, 5));
            if ($data === '[DONE]') break;
            $event = json_decode($data, true);
            $events++;
            $content = data_get($event, 'choices.0.delta.content');
            if (is_string($content) && $content !== '') {
                $firstText ??= $now;
                $lastText = $now;
                $textEvents++;
            }
        }
        $elapsed = fn ($end, $start) => $end === null ? null : round(($end - $start) * 1000);
        echo json_encode([
            'trial' => $trial,
            'prepare_ms' => $elapsed($prepared, $started),
            'headers_ms' => $elapsed($headers, $prepared),
            'first_event_ms' => $elapsed($firstEvent, $prepared),
            'first_text_ms' => $elapsed($firstText, $prepared),
            'text_delivery_span_ms' => $firstText === null ? null : $elapsed($lastText, $firstText),
            'total_ms' => $elapsed(microtime(true), $prepared),
            'events' => $events,
            'text_events' => $textEvents,
        ]).PHP_EOL;
    } catch (\Throwable $exception) {
        // Exception bodies from transport layers can contain credentials.
        echo json_encode(['trial' => $trial, 'error_type' => get_class($exception)]).PHP_EOL;
        exit(1);
    } finally {
        $body?->close();
    }
}
