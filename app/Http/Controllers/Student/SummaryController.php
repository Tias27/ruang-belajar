<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateSummaryJob;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Summary;

class SummaryController extends Controller
{
    public function store(Document $document)
    {
        abort_if($document->user_id !== auth()->id(), 403);

        $summary = Summary::create([
            'document_id' => $document->id,
            'user_id' => auth()->id(),
            'short_summary' => 'Ringkasan sedang dibuat.',
            'full_summary' => 'AI sedang membaca materi dan menyusun ringkasan. Halaman ini bisa kamu refresh beberapa saat lagi.',
            'key_points' => [],
            'conclusion' => null,
            'raw_response' => [],
            'status' => 'processing',
        ]);

        GenerateSummaryJob::dispatch($summary->id);

        return redirect()->route('summaries.show', $summary)->with('status', 'Ringkasan selesai diproses.');
    }

    public function storeFolder(\Illuminate\Http\Request $request, DocumentFolder $folder)
    {
        abort_if($folder->user_id !== auth()->id(), 403);

        $selectedDocIds = $request->input('document_ids');

        $summary = Summary::create([
            'folder_id' => $folder->id,
            'user_id' => auth()->id(),
            'short_summary' => 'Ringkasan folder sedang dibuat.',
            'full_summary' => 'AI sedang membaca semua materi dalam folder dan menyusun ringkasan. Halaman ini bisa kamu refresh beberapa saat lagi.',
            'key_points' => [],
            'conclusion' => null,
            'raw_response' => [],
            'status' => 'processing',
            'selected_document_ids' => $selectedDocIds,
        ]);

        GenerateSummaryJob::dispatch($summary->id, $selectedDocIds);

        return redirect()->route('summaries.show', $summary)->with('status', 'Ringkasan folder selesai diproses.');
    }

    public function show(Summary $summary)
    {
        abort_if($summary->user_id !== auth()->id(), 403);

        return view('student.summaries.show', ['summary' => $summary->load('document', 'folder')]);
    }
}
