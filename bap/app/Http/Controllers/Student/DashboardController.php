<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $standaloneDocuments = $user->documents()->whereNull('folder_id');
        $folderCount = $user->documentFolders()->count();

        $recentMaterials = collect()
            ->merge($user->documentFolders()
                ->withCount('documents')
                ->latest()
                ->take(4)
                ->get(['id', 'public_id', 'name', 'created_at'])
                ->map(fn ($folder) => [
                    'type' => 'folder',
                    'title' => $folder->name,
                    'meta' => $folder->documents_count.' file gabungan',
                    'icon' => 'folder',
                    'url' => route('folders.show', $folder),
                    'created_at' => $folder->created_at,
                ]))
            ->merge((clone $standaloneDocuments)
                ->latest()
                ->take(4)
                ->get(['id', 'public_id', 'title', 'extension', 'size', 'created_at'])
                ->map(fn ($document) => [
                    'type' => 'document',
                    'title' => $document->title,
                    'meta' => strtoupper($document->extension).' | '.number_format($document->size / 1024, 1).' KB',
                    'icon' => 'file-text',
                    'url' => route('documents.show', $document),
                    'created_at' => $document->created_at,
                ]))
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

        $dueFlashcards = $user->flashcards()->whereIn('study_status', ['sulit', 'ulang'])->count();
        $latestAttempt = $user->quizAttempts()->latest()->with('quiz')->first();
        $latestNote = $user->studyNotes()->latest()->first();

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
            'focusItems' => collect([
                [
                    'title' => 'Kartu perlu diulang',
                    'meta' => $dueFlashcards.' kartu bertanda sulit/ulang',
                    'icon' => 'repeat-2',
                    'url' => route('documents.index'),
                    'active' => $dueFlashcards > 0,
                ],
                [
                    'title' => 'Kuis terakhir',
                    'meta' => $latestAttempt ? $latestAttempt->score.'/'.$latestAttempt->total.' pada '.$latestAttempt->quiz?->title : 'Belum ada kuis yang dikerjakan',
                    'icon' => 'badge-check',
                    'url' => $latestAttempt ? route('quizzes.show', $latestAttempt->quiz) : route('documents.index'),
                    'active' => (bool) $latestAttempt,
                ],
                [
                    'title' => 'Catatan terbaru',
                    'meta' => $latestNote ? str($latestNote->content)->limit(80) : 'Belum ada catatan pribadi',
                    'icon' => 'notepad-text',
                    'url' => route('documents.index'),
                    'active' => (bool) $latestNote,
                ],
            ]),
            'activities' => ActivityLog::where('user_id', $user->id)
                ->latest()
                ->take(10)
                ->get(['id', 'action', 'created_at']),
        ]);
    }
}
