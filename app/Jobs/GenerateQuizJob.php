<?php

namespace App\Jobs;

use App\Models\Quiz;
use App\Services\GeminiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class GenerateQuizJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public int $quizId)
    {
        $this->onQueue('ai');
    }

    public function handle(GeminiService $gemini): void
    {
        $quiz = Quiz::with('document', 'folder')->find($this->quizId);
        if (! $quiz) {
            return;
        }

        $source = $quiz->folder ?: $quiz->document;
        if (! $source) {
            $quiz->update([
                'status' => 'failed',
                'generation_error' => 'Materi sumber tidak ditemukan.',
            ]);

            return;
        }

        try {
            $result = $gemini->generateQuiz($source, $quiz->question_type, $quiz->question_count);
            $questions = $result['questions'] ?? [];

            if (count($questions) === 0) {
                throw new \RuntimeException('AI belum mengembalikan soal yang valid.');
            }

            $quiz->questions()->delete();
            foreach ($questions as $index => $question) {
                $quiz->questions()->create([
                    'question' => $question['question'] ?? 'Pertanyaan',
                    'options' => $question['options'] ?? [],
                    'correct_answer' => $question['correct_answer'] ?? '',
                    'explanation' => $question['explanation'] ?? null,
                    'position' => $index + 1,
                ]);
            }

            $quiz->update([
                'question_count' => count($questions),
                'status' => 'completed',
                'generation_error' => null,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $quiz->update([
                'status' => 'failed',
                'generation_error' => Str::limit($exception->getMessage(), 1000, ''),
            ]);
        }
    }
}
