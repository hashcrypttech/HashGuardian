<?php

namespace Hashcrypttech\HashGuardian;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;

class HashGuardian
{
    protected bool $recording = true;

    protected ?string $currentBatchId = null;

    protected array $entriesQueue = [];

    protected array $filterCallbacks = [];

    protected array $afterStoringCallbacks = [];

    protected float $sampleRate = 1.0;

    protected ?Closure $authUsing = null;

    public function start(): void
    {
        $this->currentBatchId = Str::uuid()->toString();
        $this->entriesQueue = [];
    }

    public function isRecording(): bool
    {
        if (! $this->recording) {
            return false;
        }

        try {
            if (Cache::has('hashguardian:paused')) {
                return false;
            }
        } catch (\Throwable $e) {
            // Cache not available yet during boot
        }

        return true;
    }

    public function startRecording(): void
    {
        $this->recording = true;
    }

    public function stopRecording(): void
    {
        $this->recording = false;
    }

    public function getBatchId(): string
    {
        if ($this->currentBatchId === null) {
            $this->currentBatchId = Str::uuid()->toString();
        }

        return $this->currentBatchId;
    }

    public function record(IncomingEntry $entry): void
    {
        if (! $this->recording) {
            return;
        }

        if (! $this->shouldSample()) {
            return;
        }

        $entry->batchId($this->getBatchId());

        foreach ($this->filterCallbacks as $callback) {
            if ($callback($entry) === false) {
                return;
            }
        }

        $this->entriesQueue[] = $entry;
    }

    public function store(EntriesRepository $repository): void
    {
        if (empty($this->entriesQueue)) {
            return;
        }

        $previousState = $this->recording;
        $this->recording = false;

        try {
            $repository->store($this->entriesQueue);

            foreach ($this->afterStoringCallbacks as $callback) {
                $callback($this->entriesQueue);
            }
        } finally {
            $this->entriesQueue = [];
            $this->recording = $previousState;
        }
    }

    public function filter(Closure $callback): static
    {
        $this->filterCallbacks[] = $callback;

        return $this;
    }

    public function afterStoring(Closure $callback): static
    {
        $this->afterStoringCallbacks[] = $callback;

        return $this;
    }

    public function sample(float $rate = 1.0): static
    {
        $this->sampleRate = $rate;

        return $this;
    }

    protected function shouldSample(): bool
    {
        if ($this->sampleRate >= 1.0) {
            return true;
        }

        return mt_rand(1, 100) / 100 <= $this->sampleRate;
    }

    public function auth(Closure $callback): static
    {
        $this->authUsing = $callback;

        return $this;
    }

    public function check($request): bool
    {
        return ($this->authUsing ?: function () {
            return app()->environment('local');
        })($request);
    }

    public function getEntries(): array
    {
        return $this->entriesQueue;
    }

    public function flush(): void
    {
        $this->entriesQueue = [];
        $this->currentBatchId = null;
    }

    protected ?CustomMetrics\MetricsRecorder $metricsRecorder = null;

    protected function getMetricsRecorder(): CustomMetrics\MetricsRecorder
    {
        if ($this->metricsRecorder === null) {
            $this->metricsRecorder = new CustomMetrics\MetricsRecorder($this);
        }
        return $this->metricsRecorder;
    }

    public function count(string $name, int $increment = 1, array $metadata = []): void
    {
        $this->getMetricsRecorder()->count($name, $increment, $metadata);
    }

    public function metric(string $name, float $value, array $metadata = []): void
    {
        $this->getMetricsRecorder()->metric($name, $value, $metadata);
    }

    public function startTimer(string $name): void
    {
        $this->getMetricsRecorder()->startTimer($name);
    }

    public function stopTimer(string $name, array $metadata = []): ?float
    {
        return $this->getMetricsRecorder()->stopTimer($name, $metadata);
    }
}
