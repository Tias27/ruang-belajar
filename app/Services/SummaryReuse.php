<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Summary;
use Illuminate\Support\Facades\DB;

class SummaryReuse
{
    public function snapshot(Document|DocumentFolder $source, ?array $selectedIds = null): array
    {
        $documents = $source instanceof DocumentFolder
            ? $source->documents()->where('user_id', $source->user_id)
                ->when($selectedIds, fn ($query) => $query->whereIn('public_id', $selectedIds))->orderBy('public_id')->get()
            : collect([$source]);
        $content = $documents->map(fn (Document $document) => [
            'id' => $document->public_id, 'title' => $document->title,
            'file' => $document->file_path, 'extension' => $document->extension,
            'text' => hash('sha256', $document->extracted_text ?? ''),
        ])->all();

        return [
            'version' => 1,
            'hash' => hash('sha256', json_encode([$source->title, $content], JSON_THROW_ON_ERROR)),
            'document_ids' => $documents->pluck('public_id')->sort()->values()->all(),
            'latest_change' => $documents->pluck('updated_at')->push($source->updated_at)->filter()->max()?->toDateTimeString(),
        ];
    }

    /** Reserve once under a short row lock; AI generation runs after committing. */
    public function resolve(Document|DocumentFolder $source, ?array $selectedIds = null): array
    {
        return DB::transaction(function () use ($source, $selectedIds) {
            $source = $source->newQuery()->whereKey($source->id)->lockForUpdate()->firstOrFail();
            $snapshot = $this->snapshot($source, $selectedIds);
            $summary = $this->findReusable($source, $snapshot);
            if ($summary) return [$summary, false];

            return [Summary::create([
                $source instanceof DocumentFolder ? 'folder_id' : 'document_id' => $source->id,
                'user_id' => $source->user_id,
                'short_summary' => 'Ringkasan sedang dibuat.',
                'full_summary' => 'AI sedang membaca materi dan menyusun ringkasan.',
                'key_points' => [], 'conclusion' => null,
                'raw_response' => ['_source_snapshot' => $snapshot],
                'status' => 'processing', 'selected_document_ids' => $selectedIds,
            ]), true];
        });
    }

    private function findReusable(Document|DocumentFolder $source, array $snapshot): ?Summary
    {
        $allIds = null;
        foreach ($source->summaries()->where('user_id', $source->user_id)
            ->whereIn('status', ['completed', 'processing'])->orderByDesc('id')->cursor() as $summary) {
            $saved = $summary->raw_response['_source_snapshot'] ?? null;
            if ($summary->status === 'processing') {
                if (($saved['hash'] ?? null) === $snapshot['hash'] && $summary->created_at->gt(now()->subMinutes(6))) return $summary;
                continue;
            }
            if (blank($summary->full_summary) || $summary->full_summary === 'Ringkasan lengkap belum tersedia.') continue;
            if ($saved !== null) {
                if (($saved['hash'] ?? null) === $snapshot['hash']) return $summary;
                continue;
            }

            // Older results lack a fingerprint. Adopt only unchanged sources with the same selection.
            if ($snapshot['latest_change'] && $summary->created_at->lt($snapshot['latest_change'])) continue;
            if ($source instanceof DocumentFolder) {
                $allIds ??= $source->documents()->where('user_id', $source->user_id)->pluck('public_id')->all();
                $previousIds = collect($summary->selected_document_ids ?: $allIds)->sort()->values()->all();
                if ($previousIds !== $snapshot['document_ids']) continue;
            }
            $summary->update(['raw_response' => array_merge($summary->raw_response ?? [], ['_source_snapshot' => $snapshot])]);
            return $summary;
        }
        return null;
    }

    public function invalidateLegacy(Document $document, ?int $previousFolderId = null, bool $contentChanged = true): void
    {
        $folderIds = array_values(array_unique(array_filter([$document->folder_id, $previousFolderId])));
        $query = Summary::where('user_id', $document->user_id)->whereNull('raw_response->_source_snapshot')
            ->where(function ($query) use ($document, $folderIds, $contentChanged) {
                $query->whereIn('folder_id', $folderIds);
                if ($contentChanged) $query->orWhere('document_id', $document->id);
            });
        foreach ($query->cursor() as $summary) {
            if ($summary->folder_id && filled($summary->selected_document_ids)
                && ! in_array($document->public_id, $summary->selected_document_ids, true)) continue;
            $summary->update(['raw_response' => array_merge($summary->raw_response ?? [], [
                '_source_snapshot' => ['version' => 1, 'hash' => 'source-changed'],
            ])]);
        }
    }
}
