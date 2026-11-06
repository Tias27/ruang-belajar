<?php

namespace App\Services;

use App\Jobs\GenerateSummaryJob;
use App\Models\Document;
use Throwable;

class AutomaticSummary
{
    public function schedule(Document $document): void
    {
        if ($document->status !== 'processed' || blank($document->extracted_text)) return;

        $summary = null;
        $generate = false;
        try {
            [$summary, $generate] = app(SummaryReuse::class)->resolve($document, background: true);
            if (! $generate) return;
            dispatch((new GenerateSummaryJob($summary->id))->onConnection('ai_summaries')->onQueue('ai-summaries'));
        } catch (Throwable $exception) {
            // A queue outage must not fail an otherwise successful upload.
            report($exception);
            if ($generate && $summary) {
                $summary->newQuery()->whereKey($summary->id)->where('status', 'queued')->update([
                    'status' => 'failed', 'generation_error' => 'Ringkasan otomatis belum bisa dimulai. Klik Ringkas untuk mencoba lagi',
                ]);
            }
        }
    }
}
