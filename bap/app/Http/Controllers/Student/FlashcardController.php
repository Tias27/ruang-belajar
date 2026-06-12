<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Flashcard;
use App\Services\ActivityLogger;
use App\Services\GeminiService;
use App\Services\LearningSourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class FlashcardController extends Controller
{
    public function store(Document $document, GeminiService $gemini, ActivityLogger $logger, LearningSourceService $sources)
    {
        abort_if($document->user_id !== auth()->id(), 403);

        try {
            $result = $gemini->generateFlashcards($document);
            $flashcards = $result['flashcards'] ?? [];
            if (count($flashcards) === 0) {
                $flashcards = $sources->fallbackFlashcards($document);
            }

            foreach ($flashcards as $index => $flashcard) {
                $document->flashcards()->create([
                    'user_id' => auth()->id(),
                    'front' => $flashcard['front'] ?? 'Pertanyaan',
                    'back' => $flashcard['back'] ?? 'Jawaban',
                    'position' => $index + 1,
                ]);
            }

            $logger->log('generate_flashcards', $document, ['count' => count($flashcards)]);
        } catch (Throwable $exception) {
            report($exception);

            $flashcards = $sources->fallbackFlashcards($document);
            if (count($flashcards) === 0) {
                return back()->with('status', 'Kartu belajar belum berhasil dibuat. Coba beberapa saat lagi atau pastikan teks dokumen berhasil terbaca.');
            }

            $this->createFlashcardsForDocument($document, $flashcards);
            $logger->log('generate_flashcards_fallback', $document, ['count' => count($flashcards)]);

            return redirect()->route('flashcards.index', $document)->with('status', $this->fallbackStatus($exception));
        }

        return redirect()->route('flashcards.index', $document);
    }

    public function storeFolder(DocumentFolder $folder, GeminiService $gemini, ActivityLogger $logger, LearningSourceService $sources)
    {
        abort_if($folder->user_id !== auth()->id(), 403);

        try {
            $result = $gemini->generateFlashcards($folder);
            $flashcards = $result['flashcards'] ?? [];
            if (count($flashcards) === 0) {
                $flashcards = $sources->fallbackFlashcards($folder);
            }

            foreach ($flashcards as $index => $flashcard) {
                $folder->flashcards()->create([
                    'user_id' => auth()->id(),
                    'front' => $flashcard['front'] ?? 'Pertanyaan',
                    'back' => $flashcard['back'] ?? 'Jawaban',
                    'position' => $index + 1,
                ]);
            }

            $logger->log('generate_folder_flashcards', $folder, ['count' => count($flashcards)]);
        } catch (Throwable $exception) {
            report($exception);

            $flashcards = $sources->fallbackFlashcards($folder);
            if (count($flashcards) === 0) {
                return back()->with('status', 'Kartu belajar folder belum berhasil dibuat. Coba beberapa saat lagi atau pastikan teks dokumen di folder berhasil terbaca.');
            }

            $this->createFlashcardsForFolder($folder, $flashcards);
            $logger->log('generate_folder_flashcards_fallback', $folder, ['count' => count($flashcards)]);

            return redirect()->route('folders.flashcards.index', $folder)->with('status', $this->fallbackStatus($exception));
        }

        return redirect()->route('folders.flashcards.index', $folder);
    }

    public function index(Document $document, LearningSourceService $sources)
    {
        abort_if($document->user_id !== auth()->id(), 403);

        $flashcards = $document->flashcards()
            ->orderByRaw("FIELD(study_status, 'sulit', 'ulang', 'baru', 'paham')")
            ->orderBy('position')
            ->get();

        $this->formatFlashcardsForDisplay($flashcards, $sources);

        return view('student.flashcards.index', [
            'document' => $document,
            'folder' => null,
            'flashcards' => $flashcards,
            'stats' => $this->studyStats($document->flashcards()),
        ]);
    }

    public function indexFolder(DocumentFolder $folder, LearningSourceService $sources)
    {
        abort_if($folder->user_id !== auth()->id(), 403);

        $flashcards = $folder->flashcards()
            ->orderByRaw("FIELD(study_status, 'sulit', 'ulang', 'baru', 'paham')")
            ->orderBy('position')
            ->get();

        $this->formatFlashcardsForDisplay($flashcards, $sources);

        return view('student.flashcards.index', [
            'document' => null,
            'folder' => $folder,
            'flashcards' => $flashcards,
            'stats' => $this->studyStats($folder->flashcards()),
        ]);
    }

    public function review(Request $request, Flashcard $flashcard, ActivityLogger $logger)
    {
        abort_if($flashcard->user_id !== auth()->id(), 403);

        $data = $request->validate([
            'study_status' => ['required', 'in:paham,ulang,sulit'],
        ]);

        $flashcard->update([
            'study_status' => $data['study_status'],
            'review_count' => $flashcard->review_count + 1,
            'last_reviewed_at' => now(),
        ]);

        $logger->log('review_flashcard', $flashcard, ['status' => $data['study_status']]);

        return back()->with('status', 'Status kartu diperbarui.');
    }

    private function studyStats($query): array
    {
        return [
            'baru' => (clone $query)->where('study_status', 'baru')->count(),
            'ulang' => (clone $query)->where('study_status', 'ulang')->count(),
            'sulit' => (clone $query)->where('study_status', 'sulit')->count(),
            'paham' => (clone $query)->where('study_status', 'paham')->count(),
        ];
    }

    private function createFlashcardsForDocument(Document $document, array $flashcards): void
    {
        foreach ($flashcards as $index => $flashcard) {
            $document->flashcards()->create([
                'user_id' => auth()->id(),
                'front' => $flashcard['front'] ?? 'Pertanyaan',
                'back' => $flashcard['back'] ?? 'Jawaban',
                'position' => $index + 1,
            ]);
        }
    }

    private function createFlashcardsForFolder(DocumentFolder $folder, array $flashcards): void
    {
        foreach ($flashcards as $index => $flashcard) {
            $folder->flashcards()->create([
                'user_id' => auth()->id(),
                'front' => $flashcard['front'] ?? 'Pertanyaan',
                'back' => $flashcard['back'] ?? 'Jawaban',
                'position' => $index + 1,
            ]);
        }
    }

    private function formatFlashcardsForDisplay($flashcards, LearningSourceService $sources): void
    {
        $flashcards->each(function (Flashcard $flashcard) use ($sources) {
            $flashcard->display_back = $sources->formatFlashcardBack((string) $flashcard->back);
        });
    }

    private function fallbackStatus(Throwable $exception): string
    {
        return $this->aiFailureReason($exception).' Kartu belajar sementara dibuat dari teks materi lokal.';
    }

    private function aiFailureReason(Throwable $exception): string
    {
        $message = $exception->getMessage();
        $lower = Str::lower($message);

        return match (true) {
            str_contains($lower, 'koneksi ke gemini gagal')
                || str_contains($lower, 'could not resolve host')
                || str_contains($lower, 'resolving timed out')
                || str_contains($lower, 'curl error 6')
                || str_contains($lower, 'curl error 28')
                    => 'Koneksi ke Gemini dari server sedang gagal.',
            str_contains($lower, 'resource_exhausted')
                || str_contains($lower, 'prepayment credits are depleted')
                || str_contains($lower, 'quota')
                    => 'Kuota atau saldo API Gemini sedang habis.',
            str_contains($lower, 'unavailable') || str_contains($lower, 'high demand') || str_contains($lower, '503')
                    => 'Gemini sedang padat.',
            str_contains($lower, 'not_found') || str_contains($lower, 'not found') || str_contains($lower, '404')
                    => 'Model Gemini yang dipakai belum cocok.',
            str_contains($lower, 'api key not valid') || str_contains($lower, 'permission_denied')
                    => 'API key Gemini belum valid atau belum punya akses.',
            default => 'Gemini belum berhasil merespons.',
        };
    }
}
