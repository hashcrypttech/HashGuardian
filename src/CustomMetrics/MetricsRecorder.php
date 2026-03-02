<?php

namespace Hashcrypttech\HashGuardian\CustomMetrics;

use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;
use Hashcrypttech\HashGuardian\HashGuardian;

class MetricsRecorder
{
    protected HashGuardian $guardian;
    protected array $timers = [];

    public function __construct(HashGuardian $guardian)
    {
        $this->guardian = $guardian;
    }

    public function count(string $name, int $increment = 1, array $metadata = []): void
    {
        $this->recordMetric($name, $increment, $metadata, 'counter');
    }

    public function metric(string $name, float $value, array $metadata = []): void
    {
        $this->recordMetric($name, $value, $metadata, 'gauge');
    }

    public function startTimer(string $name): void
    {
        $this->timers[$name] = microtime(true);
    }

    public function stopTimer(string $name, array $metadata = []): ?float
    {
        if (! isset($this->timers[$name])) {
            return null;
        }

        $duration = (microtime(true) - $this->timers[$name]) * 1000;
        unset($this->timers[$name]);

        $this->recordMetric($name, round($duration, 2), $metadata, 'timer');

        return $duration;
    }

    protected function recordMetric(string $name, float $value, array $metadata, string $metricType): void
    {
        if (! config('hashguardian.custom_metrics.enabled', true)) {
            return;
        }

        $entry = IncomingEntry::make(EntryType::METRIC, [
            'name' => $name,
            'value' => $value,
            'metric_type' => $metricType,
            'metadata' => $metadata,
        ]);

        $tags = ['metric:' . $name, 'type:' . $metricType];
        foreach ($metadata as $key => $val) {
            if (is_scalar($val)) {
                $tags[] = $key . ':' . $val;
            }
        }

        $entry->tags($tags);
        $entry->familyHash(md5('metric:' . $name));

        $this->guardian->record($entry);
    }
}
