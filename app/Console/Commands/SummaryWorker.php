<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SummaryWorker extends Command
{
    protected $signature = 'ai:summary-worker {--once} {--stop-when-empty}';

    protected $description = 'Process uploaded document summaries with one worker per host';

    public function handle(): int
    {
        $lock = fopen(storage_path('framework/summary-worker.lock'), 'c');
        if (! $lock) return self::FAILURE;
        if (! flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            $this->info('Worker ringkasan sudah berjalan');
            return self::SUCCESS;
        }
        try {
            return $this->call('queue:work', [
                'connection' => 'ai_summaries', '--queue' => 'ai-summaries',
                '--sleep' => 1, '--timeout' => 360, '--tries' => 1, '--memory' => 256,
                '--once' => $this->option('once'), '--stop-when-empty' => $this->option('stop-when-empty'),
            ]);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
