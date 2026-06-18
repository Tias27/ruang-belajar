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

class DocumentFolderController extends Controller
{
    public function show(DocumentFolder $folder)
    {
        $this->authorizeOwner($folder);

        return view('student.folders.show', [
            'folder' => $folder
                ->loadCount([
                    'documents',
                    'summaries',
                    'flashcards',
                    'quizzes' => fn ($query) => $query->whereNull('study_room_id'),
                    'chatSessions'
                ])
                ->load([
                    'documents' => fn ($query) => $query->oldest(),
                    'summaries' => fn ($query) => $query->latest()->take(5),
                    'quizzes' => fn ($query) => $query->whereNull('study_room_id')->latest()->take(5),
                    'chatSessions' => fn ($query) => $query->latest()->take(5),
                    'notes' => fn ($query) => $query->where('user_id', auth()->id())->latest()->take(1),
                ]),
        ]);
    }
    public function storeDocuments(Request $request, DocumentFolder $folder, DocumentTextExtractor $extractor, ActivityLogger $logger)
    {
        $this->authorizeOwner($folder);

        $data = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:30'],
            'files.*' => ['required', 'file', 'max:20480', 'mimes:pdf,docx,pptx,jpg,jpeg,png,gif,webp'],
        ], [
            'files.required' => 'Pilih minimal satu file materi.',
            'files.max' => 'Maksimal 30 file dalam sekali upload.',
            'files.*.required' => 'File materi wajib dipilih.',
            'files.*.file' => 'File materi belum valid. Jika ukuran di atas 2 MB gagal, naikkan upload_max_filesize dan post_max_size di PHP.',
            'files.*.max' => 'Ukuran maksimal 20 MB per file.',
            'files.*.mimes' => 'Format file harus PDF, DOCX, PPTX, atau gambar (JPG, PNG, GIF, WEBP).',
        ]);

        $files = $data['files'];
        $allowedExtensions = ['pdf', 'docx', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
        $storageDirectory = storage_path('app/private/documents');

        if (! is_dir($storageDirectory)) {
            mkdir($storageDirectory, 0755, true);
        }

        foreach ($files as $index => $file) {
            $extension = strtolower($file->getClientOriginalExtension());

            if (! in_array($extension, $allowedExtensions, true)) {
                throw ValidationException::withMessages([
                    "files.{$index}" => 'Format dokumen harus PDF, DOCX, PPTX, atau gambar (JPG, PNG, GIF, WEBP).',
                ]);
            }
        }

        $documents = collect();

        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $originalName = $file->getClientOriginalName();
            $fileName = (string) Str::uuid().'.'.$extension;
            $path = 'documents/'.$fileName;
            $absolutePath = $storageDirectory.DIRECTORY_SEPARATOR.$fileName;

            $file->move($storageDirectory, $fileName);

            $document = Document::create([
                'user_id' => auth()->id(),
                'folder_id' => $folder->id,
                'title' => Str::limit(pathinfo($originalName, PATHINFO_FILENAME) ?: $originalName, 250, ''),
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_path' => $path,
                'mime_type' => $this->mimeTypeFor($extension),
                'size' => $file->getSize(),
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
                'processing_notes' => filled($text) ? null : ($processingNotes ?: 'Teks belum dapat diekstrak otomatis. AI tetap dapat digunakan setelah extractor server dilengkapi.'),
            ]);

            $logger->log('add_document_to_folder', $document, [
                'folder_id' => $folder->id,
                'title' => $document->title,
            ]);

            $documents->push($document);
        }

        return redirect()
            ->route('folders.show', $folder)
            ->with('status', $documents->count().' file berhasil ditambahkan ke folder. Hasil AI lama tetap tersimpan, buat ulang ringkasan/soal/kartu kalau ingin menyertakan file baru.');
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

    private function mimeTypeFor(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf'  => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg', 'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
