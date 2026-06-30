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

        $studentsCount = User::where('role', 'mahasiswa')->count();
        $conversationsCount = ChatMessage::where('role', 'assistant')->count();

        // Realistic dummy revenue in USD:
        // Base: $85
        // Per student: $12
        // Per AI conversation: $0.15
        $totalRevenue = 85 + ($studentsCount * 12) + ($conversationsCount * 0.15);

        return view('admin.dashboard', [
            'stats' => [
                'students' => $studentsCount,
                'conversations' => $conversationsCount,
                'total_revenue' => '$' . number_format($totalRevenue, 2, '.', ','),
            ],
            'users' => User::latest()->take(8)->get(),
            'documents' => Document::with('user')->latest()->take(8)->get(),
            'dailyUsage' => $dailyUsage,
        ]);
    }
}
