<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
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

        try {
            $result = $gemini->generateQuiz($document);
            $questions = $result['questions'] ?? [];
            if (count($questions) === 0) {
                $questions = $sources->fallbackQuiz($document);
            }
            
            $quiz = Quiz::create([
                'document_id' => $document->id,
                'user_id' => auth()->id(),
                'title' => 'Latihan: '.$document->title,
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

            $logger->log('generate_quiz', $quiz, ['document_id' => $document->id]);
        } catch (Throwable $exception) {
            report($exception);

            $questions = $sources->fallbackQuiz($document);
            if (count($questions) === 0) {
                return back()->with('status', 'Soal belum berhasil dibuat. Coba beberapa saat lagi atau pastikan teks dokumen berhasil terbaca.');
            }

            $quiz = $this->createQuiz($document, null, 'Latihan Lokal: '.$document->title, $questions);
            $logger->log('generate_quiz_fallback', $quiz, ['document_id' => $document->id]);

            return redirect()->route('quizzes.show', $quiz)->with('status', $this->fallbackStatus($exception));
        }

        return redirect()->route('quizzes.show', $quiz);
    }

    public function storeFolder(DocumentFolder $folder, GeminiService $gemini, ActivityLogger $logger, LearningSourceService $sources)
    {
        abort_if($folder->user_id !== auth()->id(), 403);

        try {
            $result = $gemini->generateQuiz($folder);
            $questions = $result['questions'] ?? [];
            if (count($questions) === 0) {
                $questions = $sources->fallbackQuiz($folder);
            }
            
            $quiz = Quiz::create([
                'folder_id' => $folder->id,
                'user_id' => auth()->id(),
                'title' => 'Latihan Folder: '.$folder->name,
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

            $logger->log('generate_folder_quiz', $quiz, ['folder_id' => $folder->id]);
        } catch (Throwable $exception) {
            report($exception);

            $questions = $sources->fallbackQuiz($folder);
            if (count($questions) === 0) {
                return back()->with('status', 'Soal folder belum berhasil dibuat. Coba beberapa saat lagi atau pastikan teks dokumen di folder berhasil terbaca.');
            }

            $quiz = $this->createQuiz(null, $folder, 'Latihan Lokal Folder: '.$folder->name, $questions);
            $logger->log('generate_folder_quiz_fallback', $quiz, ['folder_id' => $folder->id]);

            return redirect()->route('quizzes.show', $quiz)->with('status', $this->fallbackStatus($exception));
        }

        return redirect()->route('quizzes.show', $quiz);
    }

    public function show(Quiz $quiz)
    {
        abort_if($quiz->user_id !== auth()->id(), 403);

        return view('student.quizzes.show', [
            'quiz' => $quiz->load('document', 'folder', 'questions'),
            'latestAttempt' => $quiz->attempts()->where('user_id', auth()->id())->latest()->first(),
        ]);
    }

    public function storeAttempt(Request $request, Quiz $quiz, ActivityLogger $logger)
    {
        abort_if($quiz->user_id !== auth()->id(), 403);

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $quiz->load('questions');
        $answers = $data['answers'];
        $score = 0;

        foreach ($quiz->questions as $question) {
            $selected = (string) ($answers[$question->id] ?? '');
            if ($this->isCorrectAnswer($selected, (string) $question->correct_answer, $question->options ?? [])) {
                $score++;
            }
        }

        $attempt = $quiz->attempts()->create([
            'user_id' => auth()->id(),
            'answers' => $answers,
            'score' => $score,
            'total' => $quiz->questions->count(),
            'submitted_at' => now(),
        ]);

        $logger->log('submit_quiz_attempt', $attempt, ['quiz_id' => $quiz->id, 'score' => $score, 'total' => $attempt->total]);

        return redirect()->route('quizzes.show', $quiz)->with('status', 'Jawaban dikoreksi. Skor kamu '.$score.'/'.$attempt->total.'.');
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

    private function createQuiz(?Document $document, ?DocumentFolder $folder, string $title, array $questions): Quiz
    {
        $quiz = Quiz::create([
            'document_id' => $document?->id,
            'folder_id' => $folder?->id,
            'user_id' => auth()->id(),
            'title' => $title,
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

    private function normalizeAnswer(string $answer): string
    {
        $answer = preg_replace('/^[A-D][\.\)]\s*/i', '', $answer) ?: $answer;

        return Str::lower(trim((string) preg_replace('/\s+/', ' ', $answer)));
    }

    private function fallbackStatus(Throwable $exception): string
    {
        return $this->aiFailureReason($exception).' Soal sementara dibuat dari teks materi lokal.';
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
