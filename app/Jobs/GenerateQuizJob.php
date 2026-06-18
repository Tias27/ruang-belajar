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
                $options = $question['options'] ?? [];
                $correct = $question['correct_answer'] ?? '';

                if ($quiz->question_type === 'multiple_choice' && count($options) > 0) {
                    $cleanOpts = array_map(function ($opt) {
                        return trim(preg_replace('/^[A-D][\.\)]\s*/i', '', trim((string) $opt)));
                    }, $options);

                    $correctIndex = -1;
                    $correctClean = trim(preg_replace('/^[A-D][\.\)]\s*/i', '', trim((string) $correct)));
                    
                    if (preg_match('/^[A-D]$/i', $correctClean)) {
                        $correctIndex = ord(strtoupper($correctClean)) - ord('A');
                    } else {
                        foreach ($cleanOpts as $i => $cleanOpt) {
                            if (strcasecmp($cleanOpt, $correctClean) === 0) {
                                $correctIndex = $i;
                                break;
                            }
                        }
                        if ($correctIndex === -1) {
                            foreach ($options as $i => $opt) {
                                if (strcasecmp(trim($opt), trim($correct)) === 0) {
                                    $correctIndex = $i;
                                    break;
                                }
                            }
                        }
                    }

                    if ($correctIndex >= 0 && $correctIndex < count($cleanOpts)) {
                        $pairs = [];
                        foreach ($cleanOpts as $i => $text) {
                            $pairs[] = [
                                'text' => $text,
                                'is_correct' => ($i === $correctIndex)
                            ];
                        }

                        shuffle($pairs);

                        $newOpts = [];
                        $newCorrectLetter = 'A';
                        foreach ($pairs as $i => $pair) {
                            $letter = chr(ord('A') + $i);
                            $newOpts[] = $letter . '. ' . $pair['text'];
                            if ($pair['is_correct']) {
                                $newCorrectLetter = $letter;
                            }
                        }

                        $options = $newOpts;
                        $correct = $newCorrectLetter;
                    } else {
                        $newOpts = [];
                        foreach ($cleanOpts as $i => $text) {
                            $letter = chr(ord('A') + $i);
                            $newOpts[] = $letter . '. ' . $text;
                        }
                        $options = $newOpts;
                        
                        if ($correctIndex >= 0 && $correctIndex < count($cleanOpts)) {
                            $correct = chr(ord('A') + $correctIndex);
                        }
                    }
                }

                $quiz->questions()->create([
                    'question' => $question['question'] ?? 'Pertanyaan',
                    'options' => $options,
                    'correct_answer' => $correct,
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
