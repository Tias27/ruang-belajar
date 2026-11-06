<?php

// Manual live-provider check: three synthetic questions, no user data or database access.
// Console output contains metrics only; synthetic answers stay in the local test artifact.
ini_set('display_errors', '0');

try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require __DIR__.'/../bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    if (config('services.ai.provider') !== '9router') {
        throw new \RuntimeException('This manual check requires the configured 9router provider.');
    }

    $directory = storage_path('framework/testing');
    if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
        throw new \RuntimeException('Cannot create test artifact directory.');
    }
    $artifact = $directory.'/chat-rich-provider.json';
    if (@file_put_contents($artifact, "[]\n", LOCK_EX) === false) {
        throw new \RuntimeException('Cannot write test artifact.');
    }

    $cases = [
        'water_cycle' => 'Jelaskan bagaimana siklus air terjadi dan hubungan antara penguapan, pembentukan awan, hujan, serta air yang kembali ke laut.',
        'monthly_sales' => 'Data penjualan toko contoh: Januari 10 unit, Februari 15 unit, Maret 12 unit, April 22 unit. Analisis perubahan penjualan dan jelaskan kesimpulan yang bisa diambil dari data ini.',
        'linear_equation' => 'Bantu aku memahami cara menyelesaikan persamaan (2x + 3) / 5 = 7. Jelaskan langkahnya dan periksa apakah hasilnya benar.',
    ];
    $records = [];
    $failed = false;

    foreach ($cases as $label => $question) {
        $started = hrtime(true);
        $answer = '';
        $errorType = null;
        try {
            foreach (app(\App\Services\GeminiService::class)->streamChat(null, $question, richResponses: true) as $chunk) {
                $answer .= $chunk;
            }
        } catch (\Throwable $exception) {
            $errorType = get_class($exception);
            $failed = true;
        }

        $metrics = [
            'case' => $label,
            'duration_seconds' => round((hrtime(true) - $started) / 1_000_000_000, 2),
            'word_count' => count(preg_split('/\s+/u', trim($answer), -1, PREG_SPLIT_NO_EMPTY) ?: []),
            'contains_mermaid' => preg_match('/^\h*`{3,}mermaid\h*$/mi', $answer) === 1,
            'contains_chart' => preg_match('/^\h*`{3,}chart\h*$/mi', $answer) === 1,
            'contains_math' => str_contains($answer, '$$') || str_contains($answer, '\\(')
                || str_contains($answer, '\\[') || preg_match('/\$[^$\n]+\$/', $answer) === 1,
            'contains_table' => preg_match('/^\h*\|?\h*:?-{3,}:?\h*\|\h*:?-{3,}/m', $answer) === 1,
            'error_type' => $errorType,
        ];
        $records[] = ['question' => $question, 'answer' => $answer, 'metrics' => $metrics];
        $encoded = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        if (@file_put_contents($artifact, $encoded."\n", LOCK_EX) === false) {
            throw new \RuntimeException('Cannot save test result.');
        }
        echo json_encode($metrics, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
    }

    exit($failed ? 1 : 0);
} catch (\Throwable $exception) {
    echo json_encode(['error_type' => get_class($exception)], JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit(1);
}
