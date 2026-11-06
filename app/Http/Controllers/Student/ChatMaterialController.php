<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\ChatMaterials;
use App\Services\DocumentTextExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ChatMaterialController extends Controller
{
    public function store(Request $request, DocumentTextExtractor $extractor, ChatMaterials $materials)
    {
        $request->validate(['file' => ['required', 'file', 'max:20480', 'mimes:pdf,docx,pptx', 'extensions:pdf,docx,pptx']]);
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        try {
            $text = $extractor->extract($file, $extension);
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['file' => 'File belum bisa dibaca, coba file lain']);
        }
        if (blank($text)) {
            throw ValidationException::withMessages(['file' => 'Teks file belum terbaca. Untuk PDF hasil scan, lakukan OCR terlebih dahulu']);
        }
        $path = $file->store('documents', 'local');
        if (! $path) abort(503, 'File belum bisa disimpan');
        try {
            $document = Document::create([
                'user_id' => $request->user()->id,
                'title' => mb_substr(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 0, 255),
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'file_name' => basename($path), 'file_path' => $path,
                'mime_type' => $file->getMimeType(), 'extension' => $extension,
                'size' => $file->getSize(), 'status' => 'processed', 'extracted_text' => $text,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return response()->json(['material' => $materials->describe(collect([$document]))[0]], 201);
    }

    public function index(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:200'], 'page' => ['nullable', 'integer', 'min:1']]);
        $query = $request->user()->documents()->whereNotNull('extracted_text')->where('extracted_text', '!=', '');
        if (filled($data['q'] ?? null)) {
            $query->where(fn ($query) => $query->where('title', 'like', '%'.$data['q'].'%')
                ->orWhere('original_name', 'like', '%'.$data['q'].'%'));
        }
        $page = $query->latest()->orderByDesc('id')->paginate(5, ['public_id', 'title', 'original_name', 'extension']);

        return response()->json([
            'items' => $page->getCollection()->map(fn ($document) => [
                'id' => $document->public_id, 'title' => $document->title,
                'name' => $document->original_name, 'extension' => strtoupper($document->extension),
            ]),
            'page' => $page->currentPage(), 'pages' => $page->lastPage(), 'total' => $page->total(),
        ]);
    }
}
