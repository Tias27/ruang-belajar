<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateFlashcardsJob;
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
        $room = null;
        if (request()->has('room')) {
            $room = \App\Models\StudyRoom::where('uuid', request('room'))->first();
        }

        $isAuthorized = false;
        if ($document->user_id === auth()->id()) {
            $isAuthorized = true;
        } elseif ($room) {
            $isAuthorized = $room->host_id === auth()->id() || $room->users()->where('users.id', auth()->id())->exists();
        }

        abort_if(! $isAuthorized, 403);

        GenerateFlashcardsJob::dispatchSync('document', $document->id, auth()->id());
        $logger->log('queue_flashcards', $document);

        $redirectUrl = route('flashcards.index', $document);
        if ($room) {
            $redirectUrl .= '?room=' . $room->uuid;
        }

        return redirect($redirectUrl)->with('status', 'Kartu belajar selesai diproses.');
    }

    public function storeFolder(Request $request, DocumentFolder $folder, GeminiService $gemini, ActivityLogger $logger, LearningSourceService $sources)
    {
        $room = null;
        if ($request->has('room')) {
            $room = \App\Models\StudyRoom::where('uuid', $request->input('room'))->first();
        }

        $isAuthorized = false;
        if ($folder->user_id === auth()->id()) {
            $isAuthorized = true;
        } elseif ($room) {
            $isAuthorized = $room->host_id === auth()->id() || $room->users()->where('users.id', auth()->id())->exists();
        }

        abort_if(! $isAuthorized, 403);

        $selectedDocIds = $request->input('document_ids');

        GenerateFlashcardsJob::dispatchSync('folder', $folder->id, auth()->id(), $selectedDocIds);
        $logger->log('queue_folder_flashcards', $folder);

        $redirectUrl = route('folders.flashcards.index', $folder);
        if ($room) {
            $redirectUrl .= '?room=' . $room->uuid;
        }

        return redirect($redirectUrl)->with('status', 'Kartu belajar folder selesai diproses.');
    }

    public function index(Document $document, LearningSourceService $sources)
    {
        $room = null;
        if (request()->has('room')) {
            $room = \App\Models\StudyRoom::where('uuid', request('room'))->first();
        }

        $isAuthorized = false;
        if ($document->user_id === auth()->id()) {
            $isAuthorized = true;
        } elseif ($room) {
            $isAuthorized = $room->host_id === auth()->id() || $room->users()->where('users.id', auth()->id())->exists();
        }

        abort_if(! $isAuthorized, 403);

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
            'room' => $room,
        ]);
    }

    public function indexFolder(DocumentFolder $folder, LearningSourceService $sources)
    {
        $room = null;
        if (request()->has('room')) {
            $room = \App\Models\StudyRoom::where('uuid', request('room'))->first();
        }

        $isAuthorized = false;
        if ($folder->user_id === auth()->id()) {
            $isAuthorized = true;
        } elseif ($room) {
            $isAuthorized = $room->host_id === auth()->id() || $room->users()->where('users.id', auth()->id())->exists();
        }

        abort_if(! $isAuthorized, 403);

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
            'room' => $room,
        ]);
    }

    public function review(Request $request, Flashcard $flashcard, ActivityLogger $logger)
    {
        $isAuthorized = false;
        if ($flashcard->user_id === auth()->id()) {
            $isAuthorized = true;
        } else {
            $targetType = $flashcard->document_id ? \App\Models\Document::class : \App\Models\DocumentFolder::class;
            $targetId = $flashcard->document_id ?: $flashcard->folder_id;
            
            $room = \App\Models\StudyRoom::where('target_type', $targetType)
                ->where('target_id', $targetId)
                ->where('status', 'active')
                ->where(function($q) {
                    $q->where('host_id', auth()->id())
                      ->orWhereHas('users', function($uq) {
                          $uq->where('users.id', auth()->id());
                      });
                })
                ->exists();
            if ($room) {
                $isAuthorized = true;
            }
        }

        abort_if(! $isAuthorized, 403);

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
