<?php

namespace Hashcrypttech\HashGuardian\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Hashcrypttech\HashGuardian\ServerMetricsCollector;

class MonitorCommand extends Command
{
    protected $signature = 'hashguardian:monitor
        {--interval= : Seconds between samples (default from config)}
        {--disk=/ : Disk mount point to monitor}';

    protected $description = 'Start the HashGuardian server metrics monitor (long-running)';

    protected bool $shouldStop = false;

    public function handle(): void
    {
        $interval = (int) ($this->option('interval') ?? config('hashguardian.server_monitoring.interval', 15));
        $diskMount = $this->option('disk') ?? config('hashguardian.server_monitoring.disk_mount', '/');
        $connection = config('hashguardian.storage.database.connection');

        $this->info("HashGuardian server monitor started (every {$interval}s, disk: {$diskMount})");
        $this->info('Press Ctrl+C to stop.');

        $this->registerSignalHandlers();

        $collector = new ServerMetricsCollector();

        // Warm up CPU stats (needs two readings to calculate delta)
        $collector->collect($diskMount);
        sleep(1);

        while (! $this->shouldStop) {
            try {
                $metrics = $collector->collect($diskMount);

                DB::connection($connection)
                    ->table('hashguardian_server_metrics')
                    ->insert($metrics);

                $this->logMetrics($metrics);
            } catch (\Throwable $e) {
                $this->error('Collection failed: ' . $e->getMessage());
            }

            $this->sleepWithInterrupt($interval);
        }

        $this->info('HashGuardian monitor stopped.');
    }

    protected function registerSignalHandlers(): void
    {
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, fn () => $this->shouldStop = true);
            pcntl_signal(SIGINT, fn () => $this->shouldStop = true);
        }
    }

    protected function sleepWithInterrupt(int $seconds): void
    {
        for ($i = 0; $i < $seconds * 10; $i++) {
            if ($this->shouldStop) return;
            usleep(100000); // 100ms chunks
        }
    }

    protected function logMetrics(array $metrics): void
    {
        if ($this->getOutput()->isVerbose()) {
            $this->line(sprintf(
                '[%s] CPU: %.1f%% | RAM: %.1f%% | SWAP: %.1f%% | Disk: %.1f%% | Load: %.2f | Procs: %d',
                now()->format('H:i:s'),
                $metrics['cpu_percent'],
                $metrics['ram_percent'],
                $metrics['swap_percent'],
                $metrics['disk_percent'],
                $metrics['cpu_load_1m'],
                $metrics['process_count'],
            ));
        }
    }
}
