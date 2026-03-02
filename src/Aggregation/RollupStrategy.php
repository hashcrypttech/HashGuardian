<?php

namespace Hashcrypttech\HashGuardian\Aggregation;

use Illuminate\Support\Carbon;

class RollupStrategy
{
    public static function periods(): array
    {
        return ['hourly', 'daily', 'weekly', 'monthly'];
    }

    public static function rollupThresholds(): array
    {
        return [
            'hourly' => ['target' => 'daily', 'after_hours' => 48],
            'daily' => ['target' => 'weekly', 'after_hours' => 720],
            'weekly' => ['target' => 'monthly', 'after_hours' => 2160],
        ];
    }

    public static function bucketStart(Carbon $time, string $period): Carbon
    {
        return match ($period) {
            'hourly' => $time->copy()->startOfHour(),
            'daily' => $time->copy()->startOfDay(),
            'weekly' => $time->copy()->startOfWeek(),
            'monthly' => $time->copy()->startOfMonth(),
            default => $time->copy()->startOfHour(),
        };
    }

    public static function bucketInterval(string $period): string
    {
        return match ($period) {
            'hourly' => '1 hour',
            'daily' => '1 day',
            'weekly' => '1 week',
            'monthly' => '1 month',
            default => '1 hour',
        };
    }

    public static function mergeable(): array
    {
        return [
            'count' => 'sum',
            'avg_duration' => 'weighted_avg',
            'p95_duration' => 'max',
            'error_rate' => 'weighted_avg',
            'throughput' => 'avg',
            'slow_count' => 'sum',
            'duplicate_count' => 'sum',
            'fail_rate' => 'weighted_avg',
            'queue_wait_time' => 'weighted_avg',
            'unique_count' => 'max',
            'hit_count' => 'sum',
            'miss_count' => 'sum',
            'hit_ratio' => 'weighted_avg',
            'avg_cpu' => 'weighted_avg',
            'avg_memory' => 'weighted_avg',
            'peak_cpu' => 'max',
            'peak_memory' => 'max',
        ];
    }

    public static function mergeValue(string $strategy, float $existingValue, int $existingCount, float $newValue, int $newCount): float
    {
        return match ($strategy) {
            'sum' => $existingValue + $newValue,
            'max' => max($existingValue, $newValue),
            'avg' => ($existingCount + $newCount) > 0
                ? ($existingValue * $existingCount + $newValue * $newCount) / ($existingCount + $newCount)
                : 0,
            'weighted_avg' => ($existingCount + $newCount) > 0
                ? ($existingValue * $existingCount + $newValue * $newCount) / ($existingCount + $newCount)
                : 0,
            default => $newValue,
        };
    }
}
