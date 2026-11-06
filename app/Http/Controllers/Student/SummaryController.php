<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateSummaryJob;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Summary;
use App\Services\SummaryReuse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SummaryController extends Controller
{
    public function store(Document $document, SummaryReuse $reuse)
    {
        abort_if($document->user_id !== auth()->id(), 403);

        [$summary, $generate] = $reuse->resolve($document);
        if ($generate) GenerateSummaryJob::dispatchSync($summary->id);

        return $this->openResult($summary, $generate);
    }

    public function storeFolder(Request $request, DocumentFolder $folder, SummaryReuse $reuse)
    {
        abort_if($folder->user_id !== auth()->id(), 403);

        $data = $request->validate([
            'document_ids' => ['nullable', 'array'],
            'document_ids.*' => ['required', 'string', 'max:40', 'distinct'],
        ]);
        $selectedDocIds = filled($data['document_ids'] ?? null) ? array_values($data['document_ids']) : null;
        if ($selectedDocIds && $folder->documents()->where('user_id', auth()->id())->whereIn('public_id', $selectedDocIds)->count() !== count($selectedDocIds)) {
            throw ValidationException::withMessages(['document_ids' => 'Pilih file yang masih tersedia dalam folder ini']);
        }

        [$summary, $generate] = $reuse->resolve($folder, $selectedDocIds);
        if ($generate) GenerateSummaryJob::dispatchSync($summary->id, $selectedDocIds);

        return $this->openResult($summary, $generate);
    }

    private function openResult(Summary $summary, bool $generated)
    {
        $response = redirect()->route('summaries.show', $summary);
        return $summary->refresh()->status === 'completed'
            ? $response->with('status', $generated ? 'Ringkasan siap dibaca' : 'Ringkasan sebelumnya dibuka')
            : $response;
    }

    public function show(Summary $summary)
    {
        abort_if($summary->user_id !== auth()->id(), 403);

        return view('student.summaries.show', ['summary' => $summary->load('document', 'folder')]);
    }
}
