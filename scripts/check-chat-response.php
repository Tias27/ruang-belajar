<?php

// Synthetic requests only. No user history, credentials, or private material in output.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$question = 'Berikan penjelasan apa itu laut dan terbuat dari apa.';
$model = config('services.nine_router.model');
$disableTokenSaver = in_array('--token-saver-off', $argv, true);
$cases = [
    'plain_user' => [['role' => 'user', 'content' => $question]],
    'separate_roles' => [
        ['role' => 'system', 'content' => 'Kamu asisten yang menjelaskan topik secara lengkap dalam bahasa Indonesia yang mudah dipahami. Gunakan kalimat utuh dan paragraf yang mengalir.'],
        ['role' => 'user', 'content' => $question],
    ],
];
foreach ($cases as $label => $messages) {
    try {
        $response = \Illuminate\Support\Facades\Http::withToken(config('services.nine_router.api_key'))
            ->withHeaders($disableTokenSaver ? ['X-9Router-Token-Saver' => 'off'] : [])
            ->timeout(120)->connectTimeout(15)->withOptions(['allow_redirects' => false])
            ->post(rtrim(config('services.nine_router.base_url'), '/').'/chat/completions', [
                'model' => $model, 'messages' => $messages, 'max_tokens' => 8192,
                'temperature' => 0.4, 'stream' => false,
            ]);
        echo json_encode(['case' => $label, 'status' => $response->status(),
            'requested_model' => $model, 'sent_message_count' => count($messages),
            'token_saver' => $disableTokenSaver ? 'off' : 'server_default',
            'sent_content_chars' => array_sum(array_map(fn ($message) => mb_strlen($message['content']), $messages)),
            'model' => $response->json('model'), 'finish_reason' => $response->json('choices.0.finish_reason'),
            'usage' => $response->json('usage'),
            'answer' => $response->successful() ? $response->json('choices.0.message.content') : null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
    } catch (\Throwable $exception) {
        echo json_encode(['case' => $label, 'error_type' => get_class($exception)]).PHP_EOL;
    }
}
