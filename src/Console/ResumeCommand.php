<?php

namespace Hashcrypttech\HashGuardian\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ResumeCommand extends Command
{
    protected $signature = 'hashguardian:resume';

    protected $description = 'Resume HashGuardian recording';

    public function handle(): void
    {
        Cache::forget('hashguardian:paused');

        $this->info('HashGuardian recording resumed.');
    }
}
