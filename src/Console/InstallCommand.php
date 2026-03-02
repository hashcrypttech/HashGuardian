<?php

namespace Hashcrypttech\HashGuardian\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'hashguardian:install';

    protected $description = 'Install the HashGuardian package';

    public function handle(): void
    {
        $this->info('Installing HashGuardian...');

        $this->call('vendor:publish', [
            '--tag' => 'hashguardian-config',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'hashguardian-provider',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'hashguardian-assets',
            '--force' => true,
        ]);

        $this->call('migrate');

        $this->registerServiceProvider();

        $this->info('HashGuardian installed successfully.');
        $this->newLine();
        $this->info('Visit your dashboard at: ' . url(config('hashguardian.path', 'hashguardian')));
    }

    protected function registerServiceProvider(): void
    {
        $appConfig = file_get_contents(config_path('app.php'));

        if (str_contains($appConfig, 'App\\Providers\\HashGuardianServiceProvider')) {
            return;
        }

        $this->info('Remember to register App\\Providers\\HashGuardianServiceProvider in your bootstrap/providers.php');
    }
}
