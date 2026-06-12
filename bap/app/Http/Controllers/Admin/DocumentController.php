<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\DocumentDeletionService;

class DocumentController extends Controller
{
    public function index()
    {
        return view('admin.documents', ['documents' => Document::with('user')->latest()->paginate(15)]);
    }

    public function destroy(Document $document, DocumentDeletionService $deletionService)
    {
        $deletionService->delete($document);

        return back()->with('status', 'Dokumen bermasalah dihapus.');
    }
}
