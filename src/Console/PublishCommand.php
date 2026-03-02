<?php

namespace Hashcrypttech\HashGuardian\Console;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    protected $signature = 'hashguardian:publish {--force : Overwrite existing assets}';

    protected $description = 'Publish HashGuardian assets';

    public function handle(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'hashguardian-assets',
            '--force' => $this->option('force'),
        ]);

        $this->info('HashGuardian assets published successfully.');
    }
}
