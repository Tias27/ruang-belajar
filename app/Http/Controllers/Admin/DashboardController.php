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
        $dailyUsage = ActivityLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN action = 'generate_summary' THEN 1 ELSE 0 END) as summaries"),
                DB::raw("SUM(CASE WHEN action = 'generate_quiz' THEN 1 ELSE 0 END) as quizzes"),
                DB::raw("SUM(CASE WHEN action = 'generate_flashcards' THEN 1 ELSE 0 END) as flashcards"),
                DB::raw("SUM(CASE WHEN action = 'chat_document' THEN 1 ELSE 0 END) as chats")
            )
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

        // Calculate daily revenue from activity logs
        $dailyRevenue = $dailyUsage->map(function ($day) {
            $day->revenue = 5.00 + ($day->summaries * 0.50) + ($day->quizzes * 0.40) + ($day->flashcards * 0.30) + ($day->chats * 0.10);
            return $day;
        });

        // Generate 14 days of dummy member usage data
        $memberUsage = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $memberUsage[] = (object)[
                'date' => $date->format('Y-m-d'),
                'formatted_date' => $date->format('d/m'),
                'total' => rand(12, 38), // Random active members
            ];
        }

        return view('admin.dashboard', [
            'stats' => [
                'students' => $studentsCount,
                'conversations' => $conversationsCount,
                'total_revenue' => '$' . number_format($totalRevenue, 2, '.', ','),
            ],
            'users' => User::latest()->take(8)->get(),
            'documents' => Document::with('user')->latest()->take(8)->get(),
            'dailyRevenue' => $dailyRevenue,
            'memberUsage' => $memberUsage,
        ]);
    }
}
