<?php

// Read-only diagnostics: no document text, credentials, or live AI requests in output.
ini_set('display_errors', '0');
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['cache.default' => 'array', 'session.driver' => 'array', 'logging.default' => 'null']);
\Illuminate\Support\Facades\Http::preventStrayRequests();

try {
    $summaries = \App\Models\Summary::whereNotNull('folder_id')->latest('id')->limit(8)
        ->get(['id', 'folder_id', 'status', 'created_at', 'updated_at', 'generation_error', 'selected_document_ids']);
    foreach ($summaries as $summary) {
        $error = strtolower($summary->generation_error ?? '');
        echo json_encode([
            'summary_id' => $summary->id, 'folder_id' => $summary->folder_id,
            'status' => $summary->status, 'created_at' => $summary->created_at->toDateTimeString(),
            'elapsed_seconds' => $summary->created_at->diffInSeconds($summary->updated_at),
            'selected_count' => count($summary->selected_document_ids ?? []),
            'error_kind' => $error === '' ? null : (str_contains($error, 'koneksi') ? 'connection'
                : (preg_match('/timeout|time out|timed out|execution time/', $error) ? 'timeout'
                    : (str_contains($error, 'json') ? 'json' : (str_contains($error, 'http') ? 'http' : 'other')))),
        ], JSON_THROW_ON_ERROR).PHP_EOL;
    }
    $latest = $summaries->first();
    if (! $latest) exit;
    $folder = \App\Models\DocumentFolder::findOrFail($latest->folder_id);
    $selection = $latest->selected_document_ids;
    $started = hrtime(true);
    $snapshot = app(\App\Services\SummaryReuse::class)->snapshot($folder, $selection);
    $snapshotMs = (hrtime(true) - $started) / 1e6;
    $characters = $folder->documents()->when($selection, fn ($q) => $q->whereIn('public_id', $selection))
        ->selectRaw('COUNT(*) AS files, SUM(CHAR_LENGTH(extracted_text)) AS characters')->first();
    $payloadCharacters = null;
    \Illuminate\Support\Facades\Http::fake(function ($request) use (&$payloadCharacters) {
        $payloadCharacters = mb_strlen(json_encode($request->data(), JSON_THROW_ON_ERROR));
        $markdown = "## Ringkasan lengkap\nSynthetic diagnostic response for measuring local prompt preparation.\n\n"
            ."## Poin penting\n- All provider traffic is mocked.\n\n## Kesimpulan\nNo live AI request is sent.";
        $content = ['choices' => [['delta' => ['content' => $markdown], 'finish_reason' => null]]];
        $finish = ['choices' => [['delta' => [], 'finish_reason' => 'stop']]];
        $stream = 'data: '.json_encode($content, JSON_THROW_ON_ERROR)."\n\n"
            .'data: '.json_encode($finish, JSON_THROW_ON_ERROR)."\n\ndata: [DONE]\n\n";
        return \Illuminate\Support\Facades\Http::response($stream, 200, ['Content-Type' => 'text/event-stream']);
    });
    $started = hrtime(true);
    app(\App\Services\GeminiService::class)->summarize($folder, $selection);
    echo json_encode([
        'folder_id' => $folder->id, 'files' => (int) $characters->files, 'source_characters' => (int) $characters->characters,
        'fingerprint_ms' => round($snapshotMs, 2), 'preparation_with_fake_http_ms' => round((hrtime(true) - $started) / 1e6, 2),
        'payload_json_characters' => $payloadCharacters, 'peak_memory_mb' => round(memory_get_peak_usage(true) / 1048576, 1),
    ], JSON_THROW_ON_ERROR).PHP_EOL;
} catch (\Throwable $exception) {
    echo json_encode(['error_type' => get_class($exception)]).PHP_EOL;
    exit(1);
}
