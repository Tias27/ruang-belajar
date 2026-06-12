<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Services\ActivityLogger;
use App\Services\DocumentDeletionService;
use App\Services\DocumentTextExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $documents = auth()->user()->documents()->whereNull('folder_id');
        $folders = auth()->user()->documentFolders()->withCount('documents');

        if ($query !== '') {
            $documents->where(function ($builder) use ($query) {
                $builder
                    ->where('title', 'like', "%{$query}%")
                    ->orWhere('original_name', 'like', "%{$query}%")
                    ->orWhere('extension', 'like', "%{$query}%")
                    ->orWhere('extracted_text', 'like', "%{$query}%");
            });

            $folders->where(function ($builder) use ($query) {
                $builder
                    ->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereHas('documents', function ($documentQuery) use ($query) {
                        $documentQuery
                            ->where('title', 'like', "%{$query}%")
                            ->orWhere('original_name', 'like', "%{$query}%")
                            ->orWhere('extracted_text', 'like', "%{$query}%");
                    });
            });
        }

        return view('student.documents.index', [
            'documents' => $documents->latest()->paginate(10)->withQueryString(),
            'folders' => $folders->latest()->paginate(9, ['*'], 'folders_page')->withQueryString(),
            'search' => $query,
        ]);
    }

    public function create()
    {
        return view('student.documents.create');
    }

    public function store(Request $request, DocumentTextExtractor $extractor, ActivityLogger $logger)
    {
        $data = $request->validate([
            'upload_mode' => ['nullable', 'in:files,folder'],
            'title' => ['nullable', 'string', 'max:255'],
            'folder_name' => ['nullable', 'required_if:upload_mode,folder', 'string', 'max:255'],
            'folder_description' => ['nullable', 'string', 'max:1000'],
            'files' => ['required', 'array', 'min:1', 'max:30'],
            'files.*' => ['required', 'file', 'max:20480', 'mimes:pdf,docx,pptx'],
        ], [
            'files.required' => 'Pilih minimal satu file materi.',
            'files.max' => 'Maksimal 30 file dalam sekali upload.',
            'files.*.required' => 'File materi wajib dipilih.',
            'files.*.file' => 'File materi belum valid. Jika ukuran di atas 2 MB gagal, naikkan upload_max_filesize dan post_max_size di PHP.',
            'files.*.max' => 'Ukuran maksimal 20 MB per file.',
            'files.*.mimes' => 'Format file harus PDF, DOCX, atau PPTX.',
        ]);

        $files = $data['files'];
        $allowedExtensions = ['pdf', 'docx', 'pptx'];

        $storageDirectory = storage_path('app/private/documents');
        if (! is_dir($storageDirectory)) {
            mkdir($storageDirectory, 0755, true);
        }

        foreach ($files as $index => $file) {
            $extension = strtolower($file->getClientOriginalExtension());

            if (! in_array($extension, $allowedExtensions, true)) {
                throw ValidationException::withMessages([
                    "files.{$index}" => 'Format dokumen harus PDF, DOCX, atau PPTX.',
                ]);
            }
        }

        $documents = collect();
        $folder = null;
        $uploadMode = $data['upload_mode'] ?? 'files';
        $baseTitle = trim((string) ($data['title'] ?? ''));
        $totalFiles = count($files);

        if ($uploadMode === 'folder') {
            $folder = DocumentFolder::create([
                'user_id' => auth()->id(),
                'name' => trim((string) $data['folder_name']),
                'description' => $data['folder_description'] ?? null,
            ]);
            $baseTitle = '';
        }

        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $fileName = (string) Str::uuid().'.'.$extension;
            $path = 'documents/'.$fileName;
            $absolutePath = $storageDirectory.DIRECTORY_SEPARATOR.$fileName;

            $file->move($storageDirectory, $fileName);

            $document = Document::create([
                'user_id' => auth()->id(),
                'folder_id' => $folder?->id,
                'title' => $this->titleFor($baseTitle, $originalName, $totalFiles),
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_path' => $path,
                'mime_type' => $this->mimeTypeFor($extension),
                'size' => $fileSize,
                'extension' => $extension,
                'status' => 'processing',
            ]);

            $text = '';
            $processingNotes = null;

            try {
                $text = $extractor->extract($absolutePath, $extension);
            } catch (\Throwable $exception) {
                report($exception);
                $processingNotes = 'Teks belum dapat diekstrak otomatis dari dokumen ini.';
            }

            $document->update([
                'extracted_text' => $text,
                'status' => filled($text) ? 'processed' : 'uploaded',
                'processing_notes' => filled($text) ? null : ($processingNotes ?: 'Teks belum dapat diekstrak otomatis. Gemini tetap dapat digunakan setelah extractor server dilengkapi.'),
            ]);

            $logger->log('upload_document', $document, ['title' => $document->title]);
            $documents->push($document);
        }

        if ($folder) {
            $logger->log('create_document_folder', $folder, ['count' => $documents->count()]);

            return redirect()->route('folders.show', $folder)->with('status', 'Folder materi berhasil dibuat dengan '.$documents->count().' dokumen.');
        }

        if ($documents->count() === 1) {
            return redirect()->route('documents.show', $documents->first())->with('status', 'Dokumen berhasil diunggah.');
        }

        return redirect()->route('documents.index')->with('status', $documents->count().' dokumen berhasil diunggah.');
    }

    public function show(Document $document)
    {
        $this->authorizeOwner($document);

        return view('student.documents.show', [
            'document' => $document
                ->loadCount(['summaries', 'flashcards', 'quizzes', 'chatSessions'])
                ->load([
                    'summaries' => fn ($query) => $query->latest()->take(5),
                    'quizzes' => fn ($query) => $query->latest()->take(5),
                    'chatSessions' => fn ($query) => $query->latest()->take(5),
                    'notes' => fn ($query) => $query->where('user_id', auth()->id())->latest()->take(1),
                ]),
        ]);
    }

    public function download(Document $document)
    {
        $this->authorizeOwner($document);

        $absolutePath = $this->privateStoragePath($document->file_path);

        abort_if(! $absolutePath || ! is_file($absolutePath), 404);

        return response()->download($absolutePath, $document->original_name, [
            'Content-Type' => $document->mime_type ?: $this->mimeTypeFor($document->extension),
        ]);
    }

    public function bulkDestroy(Request $request, ActivityLogger $logger, DocumentDeletionService $deletionService)
    {
        $data = $request->validate([
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['string'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        $documents = auth()->user()
            ->documents()
            ->whereIn('public_id', $data['document_ids'])
            ->get();

        if ($documents->isEmpty()) {
            return redirect($this->safeRedirectPath($request->input('redirect_to')) ?: route('documents.index'))
                ->withErrors(['document_ids' => 'Tidak ada file yang bisa dihapus.']);
        }

        foreach ($documents as $document) {
            $logger->log('delete_document', $document, ['title' => $document->title, 'source' => 'bulk']);
        }

        $deletedCount = $deletionService->deleteMany($documents);
        $redirectTo = $this->safeRedirectPath($request->input('redirect_to'));

        if ($redirectTo) {
            return redirect($redirectTo)->with('status', $deletedCount.' file dihapus.');
        }

        return redirect()->route('documents.index')->with('status', $deletedCount.' file dihapus.');
    }

    public function destroy(Request $request, Document $document, ActivityLogger $logger, DocumentDeletionService $deletionService)
    {
        $this->authorizeOwner($document);

        $logger->log('delete_document', $document, ['title' => $document->title]);
        $deletionService->delete($document);

        $redirectTo = $this->safeRedirectPath($request->input('redirect_to'));

        if ($redirectTo) {
            return redirect($redirectTo)->with('status', 'File dihapus.');
        }

        return redirect()->route('documents.index')->with('status', 'File dihapus.');
    }

    private function authorizeOwner(Document $document): void
    {
        abort_if($document->user_id !== auth()->id(), 403);
    }

    private function mimeTypeFor(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            default => 'application/octet-stream',
        };
    }

    private function titleFor(string $baseTitle, string $originalName, int $totalFiles): string
    {
        $fileTitle = pathinfo($originalName, PATHINFO_FILENAME) ?: $originalName;

        if ($baseTitle !== '' && $totalFiles === 1) {
            return Str::limit($baseTitle, 250, '');
        }

        if ($baseTitle !== '') {
            return Str::limit($baseTitle.' - '.$fileTitle, 250, '');
        }

        return Str::limit($fileTitle, 250, '');
    }

    private function safeRedirectPath(?string $redirectTo): ?string
    {
        if (! is_string($redirectTo) || $redirectTo === '') {
            return null;
        }

        $path = parse_url($redirectTo, PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, '/')) {
            return null;
        }

        $query = parse_url($redirectTo, PHP_URL_QUERY);

        return is_string($query) && $query !== '' ? $path.'?'.$query : $path;
    }

    private function privateStoragePath(string $relativePath): ?string
    {
        $basePath = realpath(storage_path('app/private'));
        $targetPath = realpath(storage_path('app/private/'.$relativePath));

        if (! $basePath || ! $targetPath || ! str_starts_with($targetPath, $basePath.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $targetPath;
    }
}
