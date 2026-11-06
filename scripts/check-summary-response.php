<?php

// Manual provider probe. All source material is synthetic and no database is queried.
// Console output is metrics only; raw synthetic output is saved locally for inspection.
ini_set('display_errors', '0');

try {
    if (count($argv) > 1) {
        throw new \InvalidArgumentException('This probe takes no options; results always describe the current implementation.');
    }
    require __DIR__.'/../vendor/autoload.php';
    $app = require __DIR__.'/../bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    config(['cache.default' => 'array', 'session.driver' => 'array', 'logging.default' => 'null']);

    if (config('services.ai.provider') !== '9router') {
        throw new \RuntimeException('This manual probe requires the configured 9router provider.');
    }

    $topics = [
        ['Siklus air', 'Energi matahari menguapkan air laut dan perairan darat. Uap air naik dan mendingin lalu mengembun menjadi awan. Tetes air bergabung dan turun sebagai hujan. Air hujan meresap ke tanah atau mengalir melalui sungai menuju laut. Tumbuhan juga melepas uap air melalui transpirasi. Perubahan penggunaan lahan memengaruhi infiltrasi, limpasan, cadangan air tanah, dan risiko banjir.'],
        ['Fotosintesis dan energi', 'Klorofil dalam kloroplas menyerap energi cahaya. Tumbuhan memakai air dan karbon dioksida untuk membentuk senyawa organik, serta melepaskan oksigen. Fotosintesis menyediakan bahan organik bagi rantai makanan. Intensitas cahaya, ketersediaan air, suhu, dan konsentrasi karbon dioksida memengaruhi laju proses. Respirasi sel mengubah energi kimia yang tersimpan menjadi energi yang dapat digunakan sel.'],
        ['Ekosistem', 'Ekosistem terdiri atas komponen biotik dan abiotik yang saling berinteraksi. Produsen mengubah energi menjadi bahan organik. Konsumen memakan organisme lain. Pengurai membantu mengembalikan unsur hara ke lingkungan. Energi berkurang pada perpindahan tingkat trofik, sedangkan unsur materi mengalami daur. Keanekaragaman organisme dapat membantu kestabilan ekosistem terhadap perubahan lingkungan.'],
        ['Pengukuran ilmiah', 'Pengukuran memerlukan besaran, satuan, alat ukur, dan prosedur yang jelas. Ketelitian berbeda dengan ketepatan. Kesalahan sistematis dapat menggeser semua hasil ke arah yang sama, sedangkan variasi acak menyebabkan hasil pengulangan tersebar. Pengukuran berulang, kalibrasi, pencatatan satuan, dan evaluasi ketidakpastian membantu penafsiran data. Grafik yang baik memuat label sumbu dan skala yang sesuai.'],
        ['Statistika dasar', 'Rata-rata dihitung dengan membagi jumlah nilai dengan banyak data. Median adalah nilai tengah setelah data diurutkan, sedangkan modus merupakan nilai yang paling sering muncul. Jangkauan menggambarkan selisih nilai maksimum dan minimum. Nilai ekstrem dapat memengaruhi rata-rata lebih kuat daripada median. Korelasi menunjukkan hubungan statistik dan tidak dengan sendirinya membuktikan sebab akibat.'],
    ];

    $documents = collect();
    foreach ($topics as $index => [$title, $paragraph]) {
        $text = '';
        $unit = 1;
        while (strlen($text) < 28000) {
            $text .= "Bagian {$unit}. {$paragraph}\n\n";
            $unit++;
        }
        $documents->push(new \App\Models\Document([
            'title' => $title, 'extracted_text' => substr($text, 0, 28000),
        ]));
    }

    $folder = new class extends \App\Models\DocumentFolder {
        public \Illuminate\Support\Collection $fixtureDocuments;

        public function documentsForPrompt(?array $selectedDocIds = null): \Illuminate\Support\Collection
        {
            return $this->fixtureDocuments;
        }
    };
    $folder->name = 'Materi sains sintetis untuk uji ringkasan';
    $folder->fixtureDocuments = $documents;

    $directory = storage_path('framework/testing');
    if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
        throw new \RuntimeException('Cannot create test artifact directory.');
    }
    $artifact = $directory.'/summary-provider-sample-latest.json';
    $responses = [];
    $requestMetrics = [];

    \Illuminate\Support\Facades\Http::globalRequestMiddleware(function (\Psr\Http\Message\RequestInterface $request) use (&$requestMetrics) {
        $body = (string) $request->getBody();
        $data = json_decode($body, true);
        $requestMetrics[] = [
            'requested_max_tokens' => $data['max_tokens'] ?? null,
            'request_body_chars' => strlen($body),
            'stream' => $data['stream'] ?? null,
        ];
        return $request->withBody(\GuzzleHttp\Psr7\Utils::streamFor($body));
    });

    \Illuminate\Support\Facades\Http::globalResponseMiddleware(function (\Psr\Http\Message\ResponseInterface $response) use (&$responses) {
        if (str_contains($response->getHeaderLine('Content-Type'), 'text/event-stream')) {
            $responses[] = ['metrics' => ['status' => $response->getStatusCode(), 'stream' => true]];
            return $response;
        }
        $body = (string) $response->getBody();
        $envelope = json_decode($body, true);
        $content = $envelope['choices'][0]['message']['content'] ?? '';
        $content = is_string($content) ? $content : '';
        $finish = $envelope['choices'][0]['finish_reason'] ?? null;
        $category = match (true) {
            $response->getStatusCode() >= 400 => 'http_error',
            $finish === 'length' => 'output_limit',
            $content === '' => 'empty_content',
            default => null,
        };
        $responses[] = [
            'metrics' => [
                'status' => $response->getStatusCode(),
                'finish_reason' => in_array($finish, ['stop', 'length', 'content_filter', 'tool_calls', null], true) ? $finish : 'other',
                'output_tokens' => $envelope['usage']['completion_tokens'] ?? null,
                'content_chars' => strlen($content),
                'error_category' => $category,
            ],
            'answer' => $content,
        ];
        return $response->withBody(\GuzzleHttp\Psr7\Utils::streamFor($body));
    });

    $started = hrtime(true);
    $errorType = null;
    $result = [];
    try {
        $result = app(\App\Services\GeminiService::class)->summarize($folder);
    } catch (\Throwable $exception) {
        $errorType = get_class($exception);
    }

    $summaryBody = is_string($result['full_summary'] ?? null) ? $result['full_summary'] : '';
    $summaryWords = preg_match_all('/[\pL\pN]+/u', $summaryBody);
    $metrics = [
        'document_count' => $documents->count(),
        'source_chars' => $documents->sum(fn ($document) => strlen($document->extracted_text)),
        'duration_seconds' => round((hrtime(true) - $started) / 1_000_000_000, 2),
        'request_metrics' => $requestMetrics,
        'response_metrics' => array_column($responses, 'metrics'),
        'result_keys' => array_keys($result),
        'summary_chars' => mb_strlen($summaryBody),
        'summary_words' => $summaryWords === false ? 0 : $summaryWords,
        'key_points_count' => count($result['key_points'] ?? []),
        'artifact' => 'storage/framework/testing/summary-provider-sample-latest.json',
        'error_type' => $errorType,
    ];
    $encoded = json_encode(['metrics' => $metrics, 'responses' => $responses, 'result' => $result],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
    if (@file_put_contents($artifact, $encoded."\n", LOCK_EX) === false) {
        throw new \RuntimeException('Cannot save test artifact.');
    }
    echo json_encode($metrics, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
    exit($errorType || blank($result['full_summary'] ?? null) ? 1 : 0);
} catch (\Throwable $exception) {
    echo json_encode(['error_type' => get_class($exception)], JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit(1);
}
