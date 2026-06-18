<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConsolidateAiMemoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $target;
    protected $user;
    protected $messages;

    public function __construct($target, User $user, array $messages)
    {
        $this->target = $target;
        $this->user = $user;
        $this->messages = $messages;
    }

    public function handle(GeminiService $gemini)
    {
        $gemini->consolidateMemory($this->target, $this->user, $this->messages);
    }
}
