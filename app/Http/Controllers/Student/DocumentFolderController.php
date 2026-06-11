<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\DocumentFolder;
use App\Services\ActivityLogger;
use App\Services\DocumentDeletionService;

class DocumentFolderController extends Controller
{
    public function show(DocumentFolder $folder)
    {
        $this->authorizeOwner($folder);

        return view('student.folders.show', [
            'folder' => $folder
                ->loadCount(['documents', 'summaries', 'flashcards', 'quizzes', 'chatSessions'])
                ->load([
                    'documents' => fn ($query) => $query->oldest(),
                    'summaries' => fn ($query) => $query->latest()->take(5),
                    'quizzes' => fn ($query) => $query->latest()->take(5),
                    'chatSessions' => fn ($query) => $query->latest()->take(5),
                    'notes' => fn ($query) => $query->where('user_id', auth()->id())->latest()->take(1),
                ]),
        ]);
    }

    public function destroy(DocumentFolder $folder, ActivityLogger $logger, DocumentDeletionService $deletionService)
    {
        $this->authorizeOwner($folder);

        $documentCount = $folder->documents()->count();
        $logger->log('delete_document_folder', $folder, ['name' => $folder->name, 'document_count' => $documentCount]);
        $deletionService->deleteFolderWithDocuments($folder);

        return redirect()->route('documents.index')->with('status', 'Folder materi dan '.$documentCount.' file di dalamnya dihapus.');
    }

    private function authorizeOwner(DocumentFolder $folder): void
    {
        abort_if($folder->user_id !== auth()->id(), 403);
    }
}
