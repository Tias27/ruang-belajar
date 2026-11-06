<?php

namespace App\Jobs;

use App\Models\Summary;
use App\Services\GeminiService;
use App\Services\LearningSourceService;
use App\Services\SummaryReuse;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class GenerateSummaryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    public int $timeout = 360;

    public function __construct(public int $summaryId, public ?array $selectedDocIds = null)
    {
        $this->onQueue('ai');
    }

    public function handle(GeminiService $gemini, LearningSourceService $sources): void
    {
        // Upload workers and a user clicking Ringkas can race for the same result.
        $summary = DB::transaction(function () {
            $summary = Summary::whereKey($this->summaryId)->lockForUpdate()->first();
            if (! $summary || ! in_array($summary->status, ['queued', 'processing'], true)
                || isset($summary->raw_response['_generation_started_at'])) return null;
            $summary->update([
                'status' => 'processing',
                'raw_response' => array_merge($summary->raw_response ?? [], ['_generation_started_at' => now()->toISOString()]),
            ]);
            return $summary;
        });
        if (! $summary) {
            return;
        }
        $summary->load('document', 'folder');

        $source = $summary->folder ?: $summary->document;
        if (! $source) {
            $summary->update([
                'status' => 'failed',
                'generation_error' => 'Materi sumber tidak ditemukan.',
            ]);

            return;
        }

        // Leave time to validate and persist after the provider's 300-second deadline.
        if (function_exists('set_time_limit')) @set_time_limit(360);
        try {
            $reuse = app(SummaryReuse::class);
            $snapshot = $reuse->snapshot($source, $this->selectedDocIds);
            $reservedHash = $summary->raw_response['_source_snapshot']['hash'] ?? null;
            if ($reservedHash && $reservedHash !== $snapshot['hash']) {
                throw new \RuntimeException('Materi berubah sebelum ringkasan dimulai. Klik Ringkas untuk memakai isi terbaru');
            }
            $result = $gemini->summarize($source, $this->selectedDocIds);
            if (function_exists('set_time_limit')) @set_time_limit(60);
            if (! is_string($result['full_summary'] ?? null) || blank($result['full_summary'])) {
                throw new \UnexpectedValueException('AI belum mengembalikan isi ringkasan');
            }
            $keyPointsText = is_array($result['key_points'] ?? null)
                ? implode(' ', $result['key_points'])
                : (string) ($result['key_points'] ?? '');

            $result['source_snippets'] = $sources->snippetsFor($source, implode(' ', [
                $result['short_summary'] ?? '',
                $result['full_summary'] ?? '',
                $keyPointsText,
            ]), 3, $this->selectedDocIds);
            if ($reuse->snapshot($source->fresh(), $this->selectedDocIds)['hash'] !== $snapshot['hash']) {
                $snapshot['hash'] = 'source-changed-during-generation';
            }
            $result['_source_snapshot'] = $snapshot;
            $result['_summary_policy'] = \App\Services\SummaryLength::POLICY;

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
        } finally {
            if ($this->job && $this->job->getConnectionName() !== 'sync' && function_exists('set_time_limit')) {
                @set_time_limit(0);
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        Summary::whereKey($this->summaryId)->whereIn('status', ['queued', 'processing'])->update([
            'status' => 'failed', 'generation_error' => 'Proses ringkasan terhenti sebelum selesai. Coba lagi',
        ]);
    }
}
