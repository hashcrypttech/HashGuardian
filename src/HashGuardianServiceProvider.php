<?php

namespace Hashcrypttech\HashGuardian;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\Http\Middleware\AuthenticateApi;
use Hashcrypttech\HashGuardian\Http\Middleware\Authorize;
use Hashcrypttech\HashGuardian\Http\Middleware\ResourceSnapshot;
use Hashcrypttech\HashGuardian\Storage\DatabaseEntriesRepository;

class HashGuardianServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/hashguardian.php', 'hashguardian');

        $this->app->singleton(HashGuardian::class, function () {
            return new HashGuardian();
        });

        $this->app->singleton(EntriesRepository::class, function () {
            return new DatabaseEntriesRepository(
                config('hashguardian.storage.database.connection'),
                config('hashguardian.storage.database.chunk', 1000),
            );
        });
    }

    public function boot(): void
    {
        if (! config('hashguardian.enabled')) {
            return;
        }

        $this->registerRoutes();
        $this->registerApiRoutes();
        $this->registerPublishing();
        $this->registerCommands();
        $this->registerWatchers();
        $this->registerTermination();
    }

    protected function registerRoutes(): void
    {
        $config = [
            'prefix' => config('hashguardian.path', 'hashguardian'),
            'namespace' => 'Hashcrypttech\HashGuardian\Http\Controllers',
            'middleware' => array_merge(
                config('hashguardian.middleware', ['web']),
                [Authorize::class]
            ),
        ];

        if ($domain = config('hashguardian.domain')) {
            $config['domain'] = $domain;
        }

        Route::group($config, function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    protected function registerApiRoutes(): void
    {
        if (! config('hashguardian.api.enabled', false)) {
            return;
        }

        $config = [
            'prefix' => config('hashguardian.path', 'hashguardian') . '/' . config('hashguardian.api.prefix', 'api/v1'),
            'namespace' => 'Hashcrypttech\HashGuardian\Http\Controllers',
            'middleware' => [AuthenticateApi::class],
        ];

        if ($domain = config('hashguardian.domain')) {
            $config['domain'] = $domain;
        }

        Route::group($config, function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });
    }

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/hashguardian.php' => config_path('hashguardian.php'),
            ], 'hashguardian-config');

            $this->publishes([
                __DIR__ . '/../public' => public_path('vendor/hashguardian'),
            ], 'hashguardian-assets');

            $this->publishes([
                __DIR__ . '/../stubs/HashGuardianServiceProvider.stub' => app_path('Providers/HashGuardianServiceProvider.php'),
            ], 'hashguardian-provider');

            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'hashguardian');
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\InstallCommand::class,
                Console\PruneCommand::class,
                Console\ClearCommand::class,
                Console\PauseCommand::class,
                Console\ResumeCommand::class,
                Console\PublishCommand::class,
                Console\MonitorCommand::class,
                Console\AggregateCommand::class,
                Console\TokenCommand::class,
                Console\StatsCommand::class,
                Console\TailCommand::class,
                Console\ReportCommand::class,
            ]);
        }
    }

    protected function registerWatchers(): void
    {
        $guardian = $this->app->make(HashGuardian::class);

        if (! $guardian->isRecording()) {
            return;
        }

        $guardian->start();

        foreach (config('hashguardian.watchers', []) as $watcherClass => $options) {
            if (! is_array($options)) {
                $watcherClass = $options;
                $options = [];
            }

            if (! ($options['enabled'] ?? true)) {
                continue;
            }

            if (! class_exists($watcherClass)) {
                continue;
            }

            /** @var Watchers\Watcher $watcher */
            $watcher = new $watcherClass($options);
            $watcher->register($this->app);
        }
    }

    protected function registerTermination(): void
    {
        $storeEntries = function () {
            $guardian = $this->app->make(HashGuardian::class);
            $repository = $this->app->make(EntriesRepository::class);

            try {
                $guardian->store($repository);
            } catch (\Throwable $e) {
                logger()->error('HashGuardian: Failed to store entries', [
                    'error' => $e->getMessage(),
                ]);
            }
        };

        $this->app->terminating($storeEntries);

        if ($this->app->runningInConsole()) {
            $this->app['events']->listen(
                \Illuminate\Console\Events\CommandFinished::class,
                function ($event) use ($storeEntries) {
                    $ignoreCommands = config('hashguardian.ignore_commands', []);
                    if (! in_array($event->command, $ignoreCommands)) {
                        $storeEntries();
                    }
                }
            );
        }
    }
}
