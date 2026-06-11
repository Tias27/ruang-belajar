<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\StudyNote;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class StudyNoteController extends Controller
{
    public function storeDocument(Request $request, Document $document, ActivityLogger $logger)
    {
        abort_if($document->user_id !== auth()->id(), 403);

        $this->saveNote($request, ['document_id' => $document->id]);
        $logger->log('save_study_note', $document, ['title' => $document->title]);

        return back()->with('status', 'Catatan belajar disimpan.');
    }

    public function storeFolder(Request $request, DocumentFolder $folder, ActivityLogger $logger)
    {
        abort_if($folder->user_id !== auth()->id(), 403);

        $this->saveNote($request, ['folder_id' => $folder->id]);
        $logger->log('save_folder_note', $folder, ['name' => $folder->name]);

        return back()->with('status', 'Catatan folder disimpan.');
    }

    private function saveNote(Request $request, array $source): void
    {
        $data = $request->validate([
            'content' => ['nullable', 'string', 'max:20000'],
        ]);

        StudyNote::updateOrCreate(
            ['user_id' => auth()->id()] + $source,
            ['content' => trim((string) ($data['content'] ?? ''))]
        );
    }
}
