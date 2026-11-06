<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ConsolidateAiMemoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $target;
    protected $user;
    protected $messages;

    public $timeout = 600;
    public $tries = 0;
    public $deleteWhenMissingModels = true;
    public \DateTimeInterface $expiresAt;

    public static function enqueue($target, User $user, array $messages): void
    {
        try {
            dispatch(new static($target, $user, $messages));
        } catch (\Throwable $exception) {
            // A memory queue outage must not discard an already completed answer.
            report($exception);
        }
    }

    public function middleware(): array
    {
        // Read and write memory under the same lock, including across chat/room requests.
        $key = 'ai-memory:'.$this->user->id.':'.$this->target->getMorphClass().':'.$this->target->id;
        return [(new WithoutOverlapping($key))->releaseAfter(10)->expireAfter(650)];
    }

    public function retryUntil(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function __construct($target, User $user, array $messages)
    {
        $this->target = $target;
        $this->user = $user;
        $this->messages = $messages;
        $this->expiresAt = now()->addDay();
        $this->onConnection('ai_memory');
        $this->onQueue('ai-memory');
    }

    public function handle(GeminiService $gemini)
    {
        $gemini->consolidateMemory($this->target, $this->user, $this->messages);
    }
}
