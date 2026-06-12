<?php

namespace App\Jobs;

use App\Models\Summary;
use App\Services\GeminiService;
use App\Services\LearningSourceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class GenerateSummaryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public int $summaryId)
    {
        $this->onQueue('ai');
    }

    public function handle(GeminiService $gemini, LearningSourceService $sources): void
    {
        $summary = Summary::with('document', 'folder')->find($this->summaryId);
        if (! $summary) {
            return;
        }

        $source = $summary->folder ?: $summary->document;
        if (! $source) {
            $summary->update([
                'status' => 'failed',
                'generation_error' => 'Materi sumber tidak ditemukan.',
            ]);

            return;
        }

        try {
            $result = $gemini->summarize($source);
            $keyPointsText = is_array($result['key_points'] ?? null)
                ? implode(' ', $result['key_points'])
                : (string) ($result['key_points'] ?? '');

            $result['source_snippets'] = $sources->snippetsFor($source, implode(' ', [
                $result['short_summary'] ?? '',
                $result['full_summary'] ?? '',
                $keyPointsText,
            ]));

            $summary->update([
                'short_summary' => $result['short_summary'] ?? 'Ringkasan singkat belum tersedia.',
                'full_summary' => $result['full_summary'] ?? 'Ringkasan lengkap belum tersedia.',
                'key_points' => $result['key_points'] ?? [],
                'conclusion' => $result['conclusion'] ?? null,
                'raw_response' => $result,
                'status' => 'completed',
                'generation_error' => null,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $summary->update([
                'status' => 'failed',
                'generation_error' => Str::limit($exception->getMessage(), 1000, ''),
            ]);
        }
    }
}
