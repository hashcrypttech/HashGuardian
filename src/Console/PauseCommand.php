<?php

namespace Hashcrypttech\HashGuardian\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PauseCommand extends Command
{
    protected $signature = 'hashguardian:pause {--duration=60 : Minutes to pause recording}';

    protected $description = 'Temporarily pause HashGuardian recording';

    public function handle(): void
    {
        $minutes = (int) $this->option('duration');

        Cache::put('hashguardian:paused', true, now()->addMinutes($minutes));

        $this->info("HashGuardian recording paused for {$minutes} minutes.");
    }
}
