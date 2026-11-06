<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Capture the actual app payload so both transports send identical synthetic input.
$payload = null;
\Illuminate\Support\Facades\Http::globalRequestMiddleware(function ($request) use (&$payload) {
    $payload = (string) $request->getBody();
    return $request;
});
$prompt = (new ReflectionMethod(\App\Services\GeminiService::class, 'buildPrompt'))->invoke(
    app(\App\Services\GeminiService::class), new \App\Models\Document([
        'title' => 'Uji sintetis', 'extracted_text' => 'Alveolus adalah tempat pertukaran gas di paru-paru.',
    ]), 'Jelaskan fungsi alveolus dalam tiga kalimat.', 'Apa fungsi alveolus?', 'chat'
);
echo json_encode(['curl' => extension_loaded('curl'), 'output_buffering' => ini_get('output_buffering'),
    'zlib' => ini_get('zlib.output_compression')]).PHP_EOL;
foreach (in_array('--client-only', $argv, true) ? ['client', 'client'] : ['app', 'curl', 'curl', 'app'] as $mode) {
    $start = microtime(true);
    $first = $last = null;
    $count = 0;
    $buffer = '';
    $eventTimes = [];
    $consume = function ($chunk) use (&$buffer, &$first, &$last, &$count, &$eventTimes, $start) {
        $buffer .= $chunk;
        while (($pos = strpos($buffer, "\n")) !== false) {
            $line = trim(substr($buffer, 0, $pos));
            $buffer = substr($buffer, $pos + 1);
            if (!str_starts_with($line, 'data:')) continue;
            $event = json_decode(trim(substr($line, 5)), true);
            $text = data_get($event, 'choices.0.delta.content');
            $time = microtime(true);
            $eventTimes[] = ['ms' => round(($time - $start) * 1000),
                'text' => is_string($text) && $text !== '',
                'finish' => data_get($event, 'choices.0.finish_reason')];
            if (is_string($text) && $text !== '') { $first ??= $time; $last = $time; $count++; }
        }
    };
    try {
        if ($mode === 'client') {
            foreach (app(\App\Services\NineRouterClient::class)->stream([['text' => $prompt]], 1100) as $text) {
                $first ??= microtime(true);
                $last = microtime(true);
                $count++;
            }
            $headersMs = null;
            $encoding = null;
            $status = 200;
        } elseif ($mode === 'app') {
            $response = (new ReflectionMethod(\App\Services\NineRouterClient::class, 'request'))->invoke(
                app(\App\Services\NineRouterClient::class), [['text' => $prompt]], 1100, false, 'chat', true
            );
            $headersMs = round((microtime(true) - $start) * 1000);
            $encoding = $response->header('Content-Encoding');
            $body = $response->toPsrResponse()->getBody();
            try { while (!$body->eof()) $consume(\GuzzleHttp\Psr7\Utils::readLine($body)); }
            finally { $body->close(); }
            $status = $response->status();
        } else {
            $curl = curl_init(rtrim(config('services.nine_router.base_url'), '/').'/chat/completions');
            curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: text/event-stream',
                    'Authorization: Bearer '.config('services.nine_router.api_key')],
                CURLOPT_TIMEOUT => 60, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_WRITEFUNCTION => function ($curl, $chunk) use ($consume) { $consume($chunk); return strlen($chunk); },
            ]);
            $ok = curl_exec($curl);
            $headersMs = round(curl_getinfo($curl, CURLINFO_STARTTRANSFER_TIME) * 1000);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $encoding = null;
            $connectMs = round(curl_getinfo($curl, CURLINFO_CONNECT_TIME) * 1000);
            $error = curl_errno($curl);
            curl_close($curl);
        }
        echo json_encode(['mode' => $mode, 'status' => $status, 'headers_ms' => $headersMs,
            'first_text_ms' => $first ? round(($first - $start) * 1000) : null,
            'total_ms' => round((microtime(true) - $start) * 1000), 'encoding' => $encoding,
            'events' => $eventTimes, 'curl_error' => $mode === 'curl' ? $error : null,
            'connect_ms' => $mode === 'curl' ? $connectMs : null]).PHP_EOL;
    } catch (\Throwable $e) { echo json_encode(['mode' => $mode, 'error_type' => get_class($e)]).PHP_EOL; }
}
