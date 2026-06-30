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
        // Base: $35
        // Per student: $0.15
        // Per AI conversation: $0.02
        $totalRevenue = 35 + ($studentsCount * 0.15) + ($conversationsCount * 0.02);

        // Generate 14 days of dummy revenue and member usage data
        $dailyRevenue = [];
        $memberUsage = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $formattedDate = $date->format('d/m');

            // Dummy revenue between $15.00 and $55.00
            $dailyRevenue[] = (object)[
                'date' => $date->format('Y-m-d'),
                'formatted_date' => $formattedDate,
                'revenue' => rand(1500, 5500) / 100,
            ];

            // Dummy active members between 20 and 75
            $memberUsage[] = (object)[
                'date' => $date->format('Y-m-d'),
                'formatted_date' => $formattedDate,
                'total' => rand(20, 75),
            ];
        }

        return view('admin.dashboard', [
            'stats' => [
                'students' => $studentsCount,
                'conversations' => number_format(32800 + ($conversationsCount * 25)),
                'total_revenue' => '$' . number_format($totalRevenue, 2, '.', ','),
            ],
            'users' => User::latest()->take(8)->get(),
            'documents' => Document::with('user')->latest()->take(8)->get(),
            'dailyRevenue' => $dailyRevenue,
            'memberUsage' => $memberUsage,
        ]);
    }
}
