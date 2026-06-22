<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateQuizJob;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Quiz;
use App\Services\ActivityLogger;
use App\Services\GeminiService;
use App\Services\LearningSourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class QuizController extends Controller
{
    public function store(Document $document, GeminiService $gemini, ActivityLogger $logger, LearningSourceService $sources)
    {
        abort_if($document->user_id !== auth()->id(), 403);
        [$questionType, $questionCount] = $this->quizOptions(request());

        $studyRoomId = null;
        if (request()->has('room')) {
            $room = \App\Models\StudyRoom::where('uuid', request('room'))->first();
            if ($room) {
                $studyRoomId = $room->id;
            }
        }

        $quiz = Quiz::create([
            'document_id' => $document->id,
            'user_id' => auth()->id(),
            'study_room_id' => $studyRoomId,
            'title' => $this->quizTitle('Latihan', $questionType, $document->title),
            'question_type' => $questionType,
            'question_count' => $questionCount,
            'status' => 'processing',
        ]);

        GenerateQuizJob::dispatch($quiz->id);
        $logger->log('queue_quiz', $quiz, ['document_id' => $document->id]);

        $redirectUrl = route('quizzes.show', $quiz);
        if (request()->has('room')) {
            $redirectUrl .= '?room=' . request('room');
        }

        return redirect($redirectUrl)->with('status', 'Soal selesai diproses.');
    }

    public function storeFolder(Request $request, DocumentFolder $folder, GeminiService $gemini, ActivityLogger $logger, LearningSourceService $sources)
    {
        abort_if($folder->user_id !== auth()->id(), 403);
        [$questionType, $questionCount] = $this->quizOptions($request);

        $studyRoomId = null;
        if ($request->has('room')) {
            $room = \App\Models\StudyRoom::where('uuid', $request->input('room'))->first();
            if ($room) {
                $studyRoomId = $room->id;
            }
        }

        $selectedDocIds = $request->input('document_ids');

        $quiz = Quiz::create([
            'folder_id' => $folder->id,
            'user_id' => auth()->id(),
            'study_room_id' => $studyRoomId,
            'title' => $this->quizTitle('Latihan Folder', $questionType, $folder->name),
            'question_type' => $questionType,
            'question_count' => $questionCount,
            'status' => 'processing',
            'selected_document_ids' => $selectedDocIds,
        ]);

        GenerateQuizJob::dispatch($quiz->id);
        $logger->log('queue_folder_quiz', $quiz, ['folder_id' => $folder->id]);

        $redirectUrl = route('quizzes.show', $quiz);
        if (request()->has('room')) {
            $redirectUrl .= '?room=' . request('room');
        }

        return redirect($redirectUrl)->with('status', 'Soal folder selesai diproses.');
    }

    public function show(Quiz $quiz)
    {
        $room = null;
        if (request()->has('room')) {
            $room = \App\Models\StudyRoom::where('uuid', request('room'))->first();
        }

        $isAuthorized = false;
        if ($quiz->user_id === auth()->id()) {
            $isAuthorized = true;
        } elseif ($room) {
            $isAuthorized = $room->host_id === auth()->id() || $room->users()->where('users.id', auth()->id())->exists();
        }

        abort_if(! $isAuthorized, 403);

        $latestAttempt = null;
        if (! request()->has('retake')) {
            if ($room) {
                $latestAttempt = $quiz->attempts()
                    ->where('user_id', auth()->id())
                    ->where('created_at', '>=', $room->created_at)
                    ->latest()
                    ->first();
            } else {
                $latestAttempt = $quiz->attempts()->where('user_id', auth()->id())->latest()->first();
            }
        }

        $roomMembersAttempts = [];
        if ($room) {
            $members = collect([$room->host])->concat($room->users)->filter();
            
            $roomMembersAttempts = $members->map(function ($member) use ($quiz, $room) {
                $latest = $quiz->attempts()
                    ->where('user_id', $member->id)
                    ->where('created_at', '>=', $room->created_at)
                    ->latest()
                    ->first();
                
                return [
                    'user' => $member,
                    'attempt' => $latest,
                ];
            })->sortByDesc(function ($item) {
                return $item['attempt']?->score ?? -1;
            });
        }

        return view('student.quizzes.show', [
            'quiz' => $quiz->load('document', 'folder', 'questions'),
            'latestAttempt' => $latestAttempt,
            'room' => $room,
            'roomMembersAttempts' => $roomMembersAttempts,
        ]);
    }

    public function storeAttempt(Request $request, Quiz $quiz, GeminiService $gemini, ActivityLogger $logger)
    {
        $room = null;
        if ($request->has('room')) {
            $room = \App\Models\StudyRoom::where('uuid', $request->query('room'))->first();
        }

        $isAuthorized = false;
        if ($quiz->user_id === auth()->id()) {
            $isAuthorized = true;
        } elseif ($room) {
            $isAuthorized = $room->host_id === auth()->id() || $room->users()->where('users.id', auth()->id())->exists();
        }

        abort_if(! $isAuthorized, 403);

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string', 'max:5000'],
        ]);

        $quiz->load('questions');
        $answers = $data['answers'];
        $score = 0;
        $metadata = [];

        if ($quiz->question_type === 'essay') {
            $grading = $this->gradeEssayAttempt($quiz, $answers, $gemini);
            $metadata = $grading;
            $score = (int) collect($grading['items'] ?? [])->sum(fn (array $item) => (int) ($item['score'] ?? 0));
        } else {
            foreach ($quiz->questions as $question) {
                $selected = (string) ($answers[$question->id] ?? '');
                if ($this->isCorrectAnswer($selected, (string) $question->correct_answer, $question->options ?? [])) {
                    $score++;
                }
            }
        }

        $attempt = $quiz->attempts()->create([
            'user_id' => auth()->id(),
            'answers' => $answers,
            'metadata' => $metadata,
            'score' => $score,
            'total' => $quiz->question_type === 'essay' ? $quiz->questions->count() * 100 : $quiz->questions->count(),
            'submitted_at' => now(),
        ]);

        $logger->log('submit_quiz_attempt', $attempt, ['quiz_id' => $quiz->id, 'score' => $score, 'total' => $attempt->total]);

        $redirectUrl = route('quizzes.show', $quiz);
        if ($room) {
            $redirectUrl .= '?room=' . $room->uuid;
        }

        if ($quiz->question_type === 'essay') {
            return redirect($redirectUrl)->with('status', 'Jawaban esai sudah dinilai. Skor kamu '.$score.'/'.$attempt->total.'.');
        }

        return redirect($redirectUrl)->with('status', 'Jawaban dikoreksi. Skor kamu '.$score.'/'.$attempt->total.'.');
    }

    private function isCorrectAnswer(string $selected, string $correctAnswer, array $options): bool
    {
        $selected = trim($selected);
        $correctAnswer = trim($correctAnswer);

        if ($this->normalizeAnswer($selected) === $this->normalizeAnswer($correctAnswer)) {
            return true;
        }

        $letter = strtoupper($correctAnswer);
        if (preg_match('/^[A-D]$/', $letter)) {
            $index = ord($letter) - ord('A');

            return isset($options[$index]) && $this->normalizeAnswer($selected) === $this->normalizeAnswer((string) $options[$index]);
        }

        return false;
    }

    private function createQuiz(?Document $document, ?DocumentFolder $folder, string $title, array $questions, string $questionType = 'multiple_choice'): Quiz
    {
        $quiz = Quiz::create([
            'document_id' => $document?->id,
            'folder_id' => $folder?->id,
            'user_id' => auth()->id(),
            'title' => $title,
            'question_type' => $questionType,
            'question_count' => count($questions),
        ]);

        foreach ($questions as $index => $question) {
            $quiz->questions()->create([
                'question' => $question['question'] ?? 'Pertanyaan',
                'options' => $question['options'] ?? [],
                'correct_answer' => $question['correct_answer'] ?? '',
                'explanation' => $question['explanation'] ?? null,
                'position' => $index + 1,
            ]);
        }

        return $quiz;
    }

    private function quizOptions(Request $request): array
    {
        $data = $request->validate([
            'question_type' => ['nullable', 'in:multiple_choice,essay'],
            'question_count' => ['nullable', 'integer', 'min:1', 'max:30'],
        ], [
            'question_type.in' => 'Jenis soal belum valid.',
            'question_count.integer' => 'Jumlah soal harus berupa angka.',
            'question_count.min' => 'Jumlah soal minimal 1.',
            'question_count.max' => 'Jumlah soal maksimal 30.',
        ]);

        return [
            $data['question_type'] ?? 'multiple_choice',
            (int) ($data['question_count'] ?? 10),
        ];
    }

    private function quizTitle(string $prefix, string $questionType, string $sourceTitle): string
    {
        $typeLabel = $questionType === 'essay' ? 'Esai' : 'PG';

        return "{$prefix} {$typeLabel}: {$sourceTitle}";
    }

    private function normalizeAnswer(string $answer): string
    {
        $answer = preg_replace('/^[A-D][\.\)]\s*/i', '', $answer) ?: $answer;

        return Str::lower(trim((string) preg_replace('/\s+/', ' ', $answer)));
    }

    private function gradeEssayAttempt(Quiz $quiz, array $answers, GeminiService $gemini): array
    {
        try {
            $result = $gemini->gradeEssayQuiz($quiz, $answers);
            $items = collect($result['items'] ?? [])
                ->mapWithKeys(function (array $item) {
                    $id = (int) ($item['id'] ?? 0);

                    return [$id => [
                        'id' => $id,
                        'score' => max(0, min(100, (int) ($item['score'] ?? 0))),
                        'feedback' => trim((string) ($item['feedback'] ?? '')),
                        'suggested_answer' => trim((string) ($item['suggested_answer'] ?? '')),
                    ]];
                });

            if ($items->isNotEmpty()) {
                return [
                    'graded_by' => 'ai',
                    'summary' => trim((string) ($result['summary'] ?? '')),
                    'items' => $items->all(),
                ];
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return [
            'graded_by' => 'local',
            'summary' => 'Penilaian sementara dibuat otomatis dari kemiripan kata kunci karena AI belum berhasil menilai.',
            'items' => $quiz->questions
                ->mapWithKeys(fn ($question) => [$question->id => $this->localEssayGrade(
                    $question->id,
                    (string) ($answers[$question->id] ?? ''),
                    (string) $question->correct_answer
                )])
                ->all(),
        ];
    }

    private function localEssayGrade(int $questionId, string $answer, string $idealAnswer): array
    {
        $answerWords = collect(preg_split('/\s+/', Str::lower(strip_tags($answer))) ?: [])
            ->filter(fn (string $word) => mb_strlen($word) >= 4)
            ->unique();
        $idealWords = collect(preg_split('/\s+/', Str::lower(strip_tags($idealAnswer))) ?: [])
            ->filter(fn (string $word) => mb_strlen($word) >= 4)
            ->unique();

        $overlap = $idealWords->intersect($answerWords)->count();
        $base = $idealWords->count() > 0 ? (int) round(($overlap / max(1, $idealWords->count())) * 100) : 0;
        $lengthBonus = str_word_count($answer) >= 20 ? 10 : (str_word_count($answer) >= 8 ? 5 : 0);
        $score = max(0, min(100, $base + $lengthBonus));

        return [
            'id' => $questionId,
            'score' => $score,
            'feedback' => $score >= 70
                ? 'Jawaban sudah memuat banyak inti penting. Lengkapi dengan detail agar lebih kuat.'
                : 'Jawaban masih perlu dilengkapi dengan konsep utama dari materi.',
            'suggested_answer' => $idealAnswer,
        ];
    }

    private function fallbackStatus(Throwable $exception): string
    {
        return $this->aiFailureReason($exception).' Soal sementara dibuat dari teks materi lokal.';
    }

    private function aiFailureReason(Throwable $exception): string
    {
        $message = $exception->getMessage();
        $lower = Str::lower($message);
        $provider = str_contains($lower, 'kimchi') || str_contains($lower, 'cast.ai')
            ? 'Kimchi'
            : 'AI';

        return match (true) {
            str_contains($lower, 'koneksi ke gemini gagal')
                || str_contains($lower, 'koneksi ke kimchi gagal')
                || str_contains($lower, 'could not resolve host')
                || str_contains($lower, 'resolving timed out')
                || str_contains($lower, 'curl error 6')
                || str_contains($lower, 'curl error 28')
                    => "Koneksi ke {$provider} dari server sedang gagal.",
            str_contains($lower, 'resource_exhausted')
                || str_contains($lower, 'prepayment credits are depleted')
                || str_contains($lower, 'quota')
                    => "Kuota atau saldo API {$provider} sedang bermasalah.",
            str_contains($lower, 'unavailable') || str_contains($lower, 'high demand') || str_contains($lower, '503')
                    => "{$provider} sedang padat.",
            str_contains($lower, 'not_found') || str_contains($lower, 'not found') || str_contains($lower, '404')
                    => "Model {$provider} yang dipakai belum cocok.",
            str_contains($lower, 'api key not valid') || str_contains($lower, 'permission_denied')
                    => "API key {$provider} belum valid atau belum punya akses.",
            default => "{$provider} belum berhasil merespons.",
        };
    }
}
