<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\MaterialShelf;

class DashboardController extends Controller
{
    public function __invoke(MaterialShelf $shelf)
    {
        $user = auth()->user();
        $standaloneDocuments = $user->documents()->whereNull('folder_id');
        $folderCount = $user->documentFolders()->count();

        $recentMaterials = $shelf->paginate($user, 2, 'materials_page')->fragment('materials-title');
        $latestMaterial = $recentMaterials->currentPage() === 1
            ? $recentMaterials->first()
            : $shelf->paginate($user, 1, 'materials_page', 1)->first();

        $dueFlashcards = $user->flashcards()->whereIn('study_status', ['sulit', 'ulang'])->count();
        $reviewCard = $user->flashcards()
            ->whereIn('study_status', ['sulit', 'ulang'])
            ->with(['document', 'folder'])
            ->oldest('updated_at')
            ->first();
        $reviewSource = $reviewCard?->folder ?: $reviewCard?->document;
        $reviewUrl = $reviewSource
            ? route($reviewCard->folder ? 'folders.flashcards.index' : 'flashcards.index', $reviewSource)
            : route('documents.index');
        $latestAttempt = $user->quizAttempts()->latest()->with('quiz')->first();
        $latestNote = $user->studyNotes()->with(['document', 'folder'])->latest('updated_at')->first();
        $noteSource = $latestNote?->folder ?: $latestNote?->document;

        return view('student.dashboard', [
            'stats' => [
                'materials' => (clone $standaloneDocuments)->count() + $folderCount,
                'folders' => $folderCount,
                'documents' => (clone $standaloneDocuments)->count(),
                'summaries' => $user->summaries()->count(),
                'flashcards' => $user->flashcards()->count(),
                'quizzes' => $user->quizzes()->sum('question_count'),
                'chats' => $user->chatSessions()->count(),
                'due_flashcards' => $dueFlashcards,
            ],
            'recentMaterials' => $recentMaterials,
            'latestMaterial' => $latestMaterial,
            'reviewCard' => $reviewCard,
            'reviewSource' => $reviewSource,
            'reviewUrl' => $reviewUrl,
            'focusItems' => collect([
                [
                    'title' => 'Kartu perlu diulang',
                    'meta' => $dueFlashcards.' kartu bertanda sulit/ulang',
                    'icon' => 'repeat-2',
                    'url' => $reviewUrl,
                    'active' => $dueFlashcards > 0,
                ],
                [
                    'title' => 'Kuis terakhir',
                    'meta' => $latestAttempt ? $latestAttempt->score.'/'.$latestAttempt->total.' pada '.$latestAttempt->quiz?->title : 'Belum ada kuis yang dikerjakan',
                    'icon' => 'badge-check',
                    'url' => $latestAttempt?->quiz ? route('quizzes.show', $latestAttempt->quiz) : route('documents.index'),
                    'active' => (bool) $latestAttempt,
                ],
                [
                    'title' => 'Catatan terbaru',
                    'meta' => $latestNote ? str($latestNote->content)->limit(80) : 'Belum ada catatan pribadi',
                    'icon' => 'notepad-text',
                    'url' => $noteSource ? route($latestNote->folder ? 'folders.show' : 'documents.show', $noteSource).'#personal-notes' : route('documents.index'),
                    'active' => (bool) $latestNote,
                ],
            ]),
            'activities' => ActivityLog::where('user_id', $user->id)
                ->latest()->orderByDesc('id')
                ->paginate(4, ['id', 'action', 'created_at'], 'activity_page')
                ->withQueryString()->fragment('activity-title'),
        ]);
    }
}
