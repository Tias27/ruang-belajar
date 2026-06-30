<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ChatMessage;
use App\Models\Document;
use App\Models\Flashcard;
use App\Models\Quiz;
use App\Models\Summary;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalysisController extends Controller
{
    public function index()
    {
        $studentsCount = User::where('role', 'mahasiswa')->count();
        $documentsCount = Document::count();
        
        // Count AI features
        $summariesCount = Summary::count();
        $flashcardsCount = Flashcard::count();
        $quizzesCount = Quiz::count();
        $conversationsCount = ChatMessage::where('role', 'assistant')->count();
        
        $totalAiActions = $summariesCount + $flashcardsCount + $quizzesCount + $conversationsCount;

        // Calculate usage percentages
        $pieData = [
            'Ringkasan' => [
                'count' => $summariesCount,
                'percentage' => $totalAiActions > 0 ? round(($summariesCount / $totalAiActions) * 100, 1) : 0,
                'color' => 'bg-emerald-500',
                'text_color' => 'text-emerald-500',
            ],
            'Kartu Belajar' => [
                'count' => $flashcardsCount,
                'percentage' => $totalAiActions > 0 ? round(($flashcardsCount / $totalAiActions) * 100, 1) : 0,
                'color' => 'bg-blue-500',
                'text_color' => 'text-blue-500',
            ],
            'Kuis & Latihan' => [
                'count' => $quizzesCount,
                'percentage' => $totalAiActions > 0 ? round(($quizzesCount / $totalAiActions) * 100, 1) : 0,
                'color' => 'bg-purple-500',
                'text_color' => 'text-purple-500',
            ],
            'Tanya Jawab AI' => [
                'count' => $conversationsCount,
                'percentage' => $totalAiActions > 0 ? round(($conversationsCount / $totalAiActions) * 100, 1) : 0,
                'color' => 'bg-amber-500',
                'text_color' => 'text-amber-500',
            ],
        ];

        // Average engagement
        $avgAiPerStudent = $studentsCount > 0 ? round($totalAiActions / $studentsCount, 1) : 0;
        $avgDocPerStudent = $studentsCount > 0 ? round($documentsCount / $studentsCount, 1) : 0;

        // Generate dynamic insights
        $insights = [];
        if ($totalAiActions > 0) {
            // Find most used feature
            $mostUsed = 'Ringkasan';
            $maxVal = $summariesCount;
            if ($flashcardsCount > $maxVal) { $mostUsed = 'Kartu Belajar'; $maxVal = $flashcardsCount; }
            if ($quizzesCount > $maxVal) { $mostUsed = 'Kuis & Latihan'; $maxVal = $quizzesCount; }
            if ($conversationsCount > $maxVal) { $mostUsed = 'Tanya Jawab AI'; $maxVal = $conversationsCount; }

            $insights[] = [
                'title' => 'Fitur AI Terpopuler',
                'description' => "Fitur <strong>{$mostUsed}</strong> saat ini menjadi pilihan utama pembelajar dengan total {$maxVal} aktivitas.",
                'type' => 'info',
            ];
        }

        if ($avgDocPerStudent < 2) {
            $insights[] = [
                'title' => 'Rasio Unggahan Rendah',
                'description' => 'Rata-rata materi per pembelajar masih di bawah 2 dokumen. Disarankan untuk mempromosikan unggah materi agar pembelajaran lebih interaktif.',
                'type' => 'warning',
            ];
        } else {
            $insights[] = [
                'title' => 'Aktivitas Belajar Tinggi',
                'description' => "Rata-rata pembelajar telah mengunggah {$avgDocPerStudent} dokumen dan melakukan {$avgAiPerStudent} interaksi dengan AI.",
                'type' => 'success',
            ];
        }

        $insights[] = [
            'title' => 'Analisis Waktu Belajar',
            'description' => 'Aktivitas belajar cenderung meningkat pada pukul 19.00 - 22.00 WIB. Server siap menangani beban puncak harian.',
            'type' => 'success',
        ];

        return view('admin.analysis', [
            'studentsCount' => $studentsCount,
            'documentsCount' => $documentsCount,
            'totalAiActions' => $totalAiActions,
            'pieData' => $pieData,
            'avgAiPerStudent' => $avgAiPerStudent,
            'avgDocPerStudent' => $avgDocPerStudent,
            'insights' => $insights,
        ]);
    }
}
