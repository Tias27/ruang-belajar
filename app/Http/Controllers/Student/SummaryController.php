<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Summary;
use App\Services\ActivityLogger;
use App\Services\GeminiService;
use App\Services\LearningSourceService;
use Throwable;

class SummaryController extends Controller
{
    public function store(Document $document, GeminiService $gemini, ActivityLogger $logger, LearningSourceService $sources)
    {
        abort_if($document->user_id !== auth()->id(), 403);

        $result = $this->safeSummarize($gemini, $document);
        $keyPointsText = is_array($result['key_points'] ?? null) ? implode(' ', $result['key_points']) : (string) ($result['key_points'] ?? '');
        $result['source_snippets'] = $sources->snippetsFor($document, implode(' ', [
            $result['short_summary'] ?? '',
            $result['full_summary'] ?? '',
            $keyPointsText,
        ]));

        $summary = Summary::create([
            'document_id' => $document->id,
            'user_id' => auth()->id(),
            'short_summary' => $result['short_summary'] ?? 'Ringkasan singkat belum tersedia.',
            'full_summary' => $result['full_summary'] ?? 'Ringkasan lengkap belum tersedia.',
            'key_points' => $result['key_points'] ?? [],
            'conclusion' => $result['conclusion'] ?? null,
            'raw_response' => $result,
        ]);

        $logger->log('generate_summary', $summary, ['document_id' => $document->id]);

        return redirect()->route('summaries.show', $summary);
    }

    public function storeFolder(DocumentFolder $folder, GeminiService $gemini, ActivityLogger $logger, LearningSourceService $sources)
    {
        abort_if($folder->user_id !== auth()->id(), 403);

        $result = $this->safeSummarize($gemini, $folder);
        $keyPointsText = is_array($result['key_points'] ?? null) ? implode(' ', $result['key_points']) : (string) ($result['key_points'] ?? '');
        $result['source_snippets'] = $sources->snippetsFor($folder, implode(' ', [
            $result['short_summary'] ?? '',
            $result['full_summary'] ?? '',
            $keyPointsText,
        ]));

        $summary = Summary::create([
            'folder_id' => $folder->id,
            'user_id' => auth()->id(),
            'short_summary' => $result['short_summary'] ?? 'Ringkasan singkat belum tersedia.',
            'full_summary' => $result['full_summary'] ?? 'Ringkasan lengkap belum tersedia.',
            'key_points' => $result['key_points'] ?? [],
            'conclusion' => $result['conclusion'] ?? null,
            'raw_response' => $result,
        ]);

        $logger->log('generate_folder_summary', $summary, ['folder_id' => $folder->id]);

        return redirect()->route('summaries.show', $summary);
    }

    public function show(Summary $summary)
    {
        abort_if($summary->user_id !== auth()->id(), 403);

        return view('student.summaries.show', ['summary' => $summary->load('document', 'folder')]);
    }

    private function safeSummarize(GeminiService $gemini, Document|DocumentFolder $source): array
    {
        try {
            return $gemini->summarize($source);
        } catch (Throwable $exception) {
            report($exception);

            return [
                'short_summary' => 'Ringkasan belum berhasil dibuat.',
                'full_summary' => 'Layanan AI belum berhasil merespons. Coba beberapa saat lagi atau gunakan dokumen dengan teks yang lebih jelas.',
                'key_points' => [],
                'conclusion' => 'Ringkasan belum dapat dibuat saat ini.',
            ];
        }
    }
}
