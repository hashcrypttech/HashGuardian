<?php

namespace Hashcrypttech\HashGuardian\Console;

use Illuminate\Console\Command;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;

class ClearCommand extends Command
{
    protected $signature = 'hashguardian:clear';

    protected $description = 'Clear all entries from the HashGuardian database';

    public function handle(EntriesRepository $repository): void
    {
        if (! $this->confirm('This will remove all HashGuardian data. Continue?')) {
            return;
        }

        $repository->clear();

        $this->info('All HashGuardian entries have been cleared.');
    }
}
