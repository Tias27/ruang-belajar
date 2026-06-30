<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ChatMessage;
use App\Models\Document;
use App\Models\Flashcard;
use App\Models\QuizQuestion;
use App\Models\Summary;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $dailyUsage = ActivityLog::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->whereIn('action', ['generate_summary', 'generate_quiz', 'generate_flashcards', 'chat_document'])
            ->groupBy('date')
            ->orderBy('date')
            ->limit(14)
            ->get();

        return view('admin.dashboard', [
            'stats' => [
                'students' => User::where('role', 'mahasiswa')->count(),
                'total_revenue' => '$120',
                'documents' => Document::count(),
                'summaries' => Summary::count(),
                'flashcards' => Flashcard::count(),
                'questions' => QuizQuestion::count(),
                'conversations' => ChatMessage::where('role', 'assistant')->count(),
            ],
            'users' => User::latest()->take(8)->get(),
            'documents' => Document::with('user')->latest()->take(8)->get(),
            'dailyUsage' => $dailyUsage,
        ]);
    }
}
