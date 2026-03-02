<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Str;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;
use Hashcrypttech\HashGuardian\ServerMetricsCollector;
use Hashcrypttech\HashGuardian\HashGuardian;

class JobWatcher extends Watcher
{
    protected array $jobStartTimes = [];

    public function register(Application $app): void
    {
        $app['events']->listen(JobQueued::class, [$this, 'handleJobQueued']);
        $app['events']->listen(JobProcessing::class, [$this, 'handleJobProcessing']);
        $app['events']->listen(JobProcessed::class, [$this, 'handleJobProcessed']);
        $app['events']->listen(JobFailed::class, [$this, 'handleJobFailed']);
    }

    public function handleJobQueued(JobQueued $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $payload = $this->extractPayload($event->job);

        $entry = IncomingEntry::make(EntryType::JOB, [
            'name' => $this->getJobName($event->job),
            'queue' => method_exists($event->job, 'queue') ? $event->job->queue : null,
            'connection' => $event->connectionName ?? null,
            'status' => 'queued',
            'data' => $payload,
        ]);

        $entry->status('queued');
        $entry->familyHash(md5($this->getJobName($event->job)));
        $entry->tags([
            'job:' . $this->getJobName($event->job),
            'status:queued',
        ]);

        $this->record($entry);
    }

    public function handleJobProcessing(JobProcessing $event): void
    {
        $jobId = $event->job->getJobId();
        $this->jobStartTimes[$jobId] = microtime(true);

        $guardian = app(HashGuardian::class);

        try {
            $repository = app(EntriesRepository::class);
            $guardian->store($repository);
        } catch (\Throwable $e) {
        }

        $guardian->start();
    }

    public function handleJobProcessed(JobProcessed $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $jobId = $event->job->getJobId();
        $duration = isset($this->jobStartTimes[$jobId])
            ? (microtime(true) - $this->jobStartTimes[$jobId]) * 1000
            : null;

        unset($this->jobStartTimes[$jobId]);

        $payload = json_decode($event->job->getRawBody(), true) ?: [];

        $entry = IncomingEntry::make(EntryType::JOB, [
            'name' => $event->job->resolveName(),
            'job' => $event->job->resolveName(),
            'queue' => $event->job->getQueue(),
            'connection' => $event->connectionName,
            'status' => 'processed',
            'attempts' => $event->job->attempts(),
            'payload' => $this->truncatePayload($payload),
        ]);

        $entry->duration($duration);
        $entry->status('processed');
        $entry->familyHash(md5($event->job->resolveName()));
        $entry->tags([
            'job:' . $event->job->resolveName(),
            'queue:' . $event->job->getQueue(),
            'status:processed',
        ]);

        $this->record($entry);

        try {
            $guardian = app(HashGuardian::class);
            $repository = app(EntriesRepository::class);
            $guardian->store($repository);
        } catch (\Throwable $e) {
        }
    }

    public function handleJobFailed(JobFailed $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $jobId = $event->job->getJobId();
        $duration = isset($this->jobStartTimes[$jobId])
            ? (microtime(true) - $this->jobStartTimes[$jobId]) * 1000
            : null;

        unset($this->jobStartTimes[$jobId]);

        $payload = json_decode($event->job->getRawBody(), true) ?: [];

        $entry = IncomingEntry::make(EntryType::JOB, [
            'name' => $event->job->resolveName(),
            'job' => $event->job->resolveName(),
            'queue' => $event->job->getQueue(),
            'connection' => $event->connectionName,
            'status' => 'failed',
            'attempts' => $event->job->attempts(),
            'exception' => [
                'class' => get_class($event->exception),
                'message' => $event->exception->getMessage(),
                'file' => $event->exception->getFile(),
                'line' => $event->exception->getLine(),
            ],
            'payload' => $this->truncatePayload($payload),
        ]);

        $entry->duration($duration);
        $entry->status('failed');
        $entry->familyHash(md5($event->job->resolveName()));
        $entry->tags([
            'job:' . $event->job->resolveName(),
            'queue:' . $event->job->getQueue(),
            'status:failed',
            'exception:' . get_class($event->exception),
        ]);

        $this->record($entry);

        try {
            $guardian = app(HashGuardian::class);
            $repository = app(EntriesRepository::class);
            $guardian->store($repository);
        } catch (\Throwable $e) {
        }
    }

    protected function getJobName($job): string
    {
        if (is_object($job)) {
            return get_class($job);
        }

        return (string) $job;
    }

    protected function extractPayload($job): ?array
    {
        if (method_exists($job, 'toArray')) {
            return $job->toArray();
        }

        return null;
    }

    protected function truncatePayload(array $payload): array
    {
        $data = $payload['data'] ?? $payload;

        if (isset($data['command'])) {
            $data['command'] = Str::limit($data['command'], 500);
        }

        return $data;
    }

    protected function getJobResourceDelta(string $jobId): array
    {
        $startSnapshot = $this->jobResourceSnapshots[$jobId] ?? null;
        unset($this->jobResourceSnapshots[$jobId]);

        if (! $startSnapshot) {
            return [];
        }

        $endSnapshot = ServerMetricsCollector::snapshot();
        $delta = ServerMetricsCollector::delta($startSnapshot, $endSnapshot);

        return [
            'resource_usage' => [
                'memory_start_mb' => round($delta['memory_start'] / 1024 / 1024, 2),
                'memory_peak_mb' => round($delta['memory_peak'] / 1024 / 1024, 2),
                'memory_delta_mb' => round($delta['memory_delta'] / 1024 / 1024, 2),
                'cpu_user_ms' => round($delta['cpu_user_time_us'] / 1000, 2),
                'cpu_system_ms' => round($delta['cpu_system_time_us'] / 1000, 2),
                'cpu_total_ms' => round($delta['cpu_total_time_us'] / 1000, 2),
            ],
        ];
    }
}
