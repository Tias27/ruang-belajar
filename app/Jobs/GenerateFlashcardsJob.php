<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentFolder;
use App\Services\GeminiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateFlashcardsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public string $sourceType,
        public int $sourceId,
        public int $userId,
    ) {
        $this->onQueue('ai');
    }

    public function handle(GeminiService $gemini): void
    {
        $source = $this->source();
        if (! $source) {
            return;
        }

        try {
            $result = $gemini->generateFlashcards($source);
            $flashcards = $result['flashcards'] ?? [];

            if (count($flashcards) === 0) {
                throw new \RuntimeException('AI belum mengembalikan kartu belajar yang valid.');
            }

            $nextPosition = ($source->flashcards()->max('position') ?? 0) + 1;
            foreach ($flashcards as $index => $flashcard) {
                $source->flashcards()->create([
                    'user_id' => $this->userId,
                    'front' => $flashcard['front'] ?? 'Pertanyaan',
                    'back' => $flashcard['back'] ?? 'Jawaban',
                    'position' => $nextPosition + $index,
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function source(): Document|DocumentFolder|null
    {
        return $this->sourceType === 'folder'
            ? DocumentFolder::find($this->sourceId)
            : Document::find($this->sourceId);
    }
}
