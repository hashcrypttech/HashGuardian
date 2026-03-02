<?php

namespace Hashcrypttech\HashGuardian;

class ServerMetricsCollector
{
    protected ?array $previousCpuStats = null;
    protected ?array $previousNetStats = null;

    public function collect(string $diskMount = '/'): array
    {
        return [
            ...$this->collectCpu(),
            ...$this->collectRam(),
            ...$this->collectSwap(),
            ...$this->collectDisk($diskMount),
            ...$this->collectNetwork(),
            ...$this->collectProcesses(),
            ...$this->collectPhp(),
            'disk_mount' => $diskMount,
            'created_at' => now(),
        ];
    }

    public function collectCpu(): array
    {
        $loadAvg = sys_getloadavg() ?: [0, 0, 0];
        $cores = $this->getCpuCores();
        $cpuPercent = $this->calculateCpuPercent();

        return [
            'cpu_percent' => round($cpuPercent, 2),
            'cpu_load_1m' => round($loadAvg[0], 2),
            'cpu_load_5m' => round($loadAvg[1], 2),
            'cpu_load_15m' => round($loadAvg[2], 2),
            'cpu_cores' => $cores,
        ];
    }

    protected function calculateCpuPercent(): float
    {
        $stats = $this->readCpuStats();

        if (! $stats) {
            $loadAvg = sys_getloadavg();
            $cores = $this->getCpuCores();
            return min(100, ($loadAvg[0] / max(1, $cores)) * 100);
        }

        if ($this->previousCpuStats === null) {
            $this->previousCpuStats = $stats;
            usleep(100000); // 100ms sample
            $stats = $this->readCpuStats();
            if (! $stats) return 0;
        }

        $prevIdle = $this->previousCpuStats['idle'] + $this->previousCpuStats['iowait'];
        $currIdle = $stats['idle'] + $stats['iowait'];

        $prevTotal = array_sum($this->previousCpuStats);
        $currTotal = array_sum($stats);

        $totalDiff = $currTotal - $prevTotal;
        $idleDiff = $currIdle - $prevIdle;

        $this->previousCpuStats = $stats;

        if ($totalDiff === 0) return 0;

        return (($totalDiff - $idleDiff) / $totalDiff) * 100;
    }

    protected function readCpuStats(): ?array
    {
        if (! is_readable('/proc/stat')) {
            return null;
        }

        $line = file('/proc/stat')[0] ?? null;
        if (! $line || ! str_starts_with($line, 'cpu ')) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($line));
        array_shift($parts); // remove 'cpu'

        return [
            'user' => (int) ($parts[0] ?? 0),
            'nice' => (int) ($parts[1] ?? 0),
            'system' => (int) ($parts[2] ?? 0),
            'idle' => (int) ($parts[3] ?? 0),
            'iowait' => (int) ($parts[4] ?? 0),
            'irq' => (int) ($parts[5] ?? 0),
            'softirq' => (int) ($parts[6] ?? 0),
            'steal' => (int) ($parts[7] ?? 0),
        ];
    }

    public function getCpuCores(): int
    {
        if (is_readable('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            return max(1, substr_count($cpuinfo, 'processor'));
        }

        return 1;
    }

    public function collectRam(): array
    {
        $meminfo = $this->parseMeminfo();

        $total = ($meminfo['MemTotal'] ?? 0) * 1024;
        $free = ($meminfo['MemFree'] ?? 0) * 1024;
        $available = ($meminfo['MemAvailable'] ?? $free) * 1024;
        $buffers = ($meminfo['Buffers'] ?? 0) * 1024;
        $cached = ($meminfo['Cached'] ?? 0) * 1024;

        $used = $total - $free - $buffers - $cached;
        if ($used < 0) $used = $total - $free;

        $percent = $total > 0 ? (($total - $available) / $total) * 100 : 0;

        return [
            'ram_total' => $total,
            'ram_used' => max(0, $used),
            'ram_free' => $free,
            'ram_available' => $available,
            'ram_percent' => round($percent, 2),
        ];
    }

    public function collectSwap(): array
    {
        $meminfo = $this->parseMeminfo();

        $total = ($meminfo['SwapTotal'] ?? 0) * 1024;
        $free = ($meminfo['SwapFree'] ?? 0) * 1024;
        $used = $total - $free;
        $percent = $total > 0 ? ($used / $total) * 100 : 0;

        return [
            'swap_total' => $total,
            'swap_used' => max(0, $used),
            'swap_free' => $free,
            'swap_percent' => round($percent, 2),
        ];
    }

    protected function parseMeminfo(): array
    {
        if (! is_readable('/proc/meminfo')) {
            return [];
        }

        $meminfo = [];
        foreach (file('/proc/meminfo') as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $m)) {
                $meminfo[$m[1]] = (int) $m[2];
            }
        }

        return $meminfo;
    }

    public function collectDisk(string $mount = '/'): array
    {
        $total = @disk_total_space($mount) ?: 0;
        $free = @disk_free_space($mount) ?: 0;
        $used = $total - $free;
        $percent = $total > 0 ? ($used / $total) * 100 : 0;

        return [
            'disk_total' => (int) $total,
            'disk_used' => (int) max(0, $used),
            'disk_free' => (int) $free,
            'disk_percent' => round($percent, 2),
        ];
    }

    public function collectNetwork(): array
    {
        $stats = $this->readNetworkStats();

        if ($this->previousNetStats !== null) {
            $bytesIn = max(0, $stats['bytes_in'] - $this->previousNetStats['bytes_in']);
            $bytesOut = max(0, $stats['bytes_out'] - $this->previousNetStats['bytes_out']);
        } else {
            $bytesIn = 0;
            $bytesOut = 0;
        }

        $this->previousNetStats = $stats;

        return [
            'net_bytes_in' => $bytesIn,
            'net_bytes_out' => $bytesOut,
        ];
    }

    protected function readNetworkStats(): array
    {
        $bytesIn = 0;
        $bytesOut = 0;

        if (! is_readable('/proc/net/dev')) {
            return ['bytes_in' => 0, 'bytes_out' => 0];
        }

        foreach (file('/proc/net/dev') as $line) {
            $line = trim($line);
            if (! str_contains($line, ':')) continue;

            [$iface, $data] = explode(':', $line, 2);
            $iface = trim($iface);

            if ($iface === 'lo') continue;

            $parts = preg_split('/\s+/', trim($data));
            $bytesIn += (int) ($parts[0] ?? 0);
            $bytesOut += (int) ($parts[8] ?? 0);
        }

        return ['bytes_in' => $bytesIn, 'bytes_out' => $bytesOut];
    }

    public function collectProcesses(): array
    {
        $processCount = 0;
        $fpmActive = null;
        $fpmIdle = null;

        if (is_dir('/proc')) {
            $dirs = @scandir('/proc');
            if ($dirs) {
                foreach ($dirs as $dir) {
                    if (is_numeric($dir)) $processCount++;
                }
            }
        }

        $fpmStatus = $this->getPhpFpmStatus();
        if ($fpmStatus) {
            $fpmActive = $fpmStatus['active'] ?? null;
            $fpmIdle = $fpmStatus['idle'] ?? null;
        }

        return [
            'process_count' => $processCount,
            'php_fpm_active' => $fpmActive,
            'php_fpm_idle' => $fpmIdle,
        ];
    }

    protected function getPhpFpmStatus(): ?array
    {
        $statusUrl = config('hashguardian.server_monitoring.fpm_status_url');

        if (! $statusUrl) {
            return null;
        }

        try {
            $response = @file_get_contents($statusUrl . '?json');
            if ($response) {
                $data = json_decode($response, true);
                return [
                    'active' => $data['active processes'] ?? null,
                    'idle' => $data['idle processes'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            // Silently fail
        }

        return null;
    }

    public function collectPhp(): array
    {
        $memoryLimit = $this->parseBytes(ini_get('memory_limit'));
        $opcacheUsed = null;

        if (function_exists('opcache_get_status')) {
            $status = @opcache_get_status(false);
            if ($status && isset($status['memory_usage'])) {
                $used = $status['memory_usage']['used_memory'] ?? 0;
                $free = $status['memory_usage']['free_memory'] ?? 0;
                $total = $used + $free;
                $opcacheUsed = $total > 0 ? round(($used / $total) * 100, 2) : null;
            }
        }

        return [
            'php_memory_limit' => $memoryLimit,
            'opcache_used' => $opcacheUsed,
        ];
    }

    protected function parseBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '-1') {
            return -1;
        }

        $unit = strtolower(substr($value, -1));
        $num = (int) $value;

        return match ($unit) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => $num,
        };
    }

    /**
     * Take a snapshot for per-request/per-job resource tracking.
     */
    public static function snapshot(): array
    {
        $rusage = getrusage();

        return [
            'memory' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'cpu_user_time' => ($rusage['ru_utime.tv_sec'] ?? 0) * 1e6 + ($rusage['ru_utime.tv_usec'] ?? 0),
            'cpu_system_time' => ($rusage['ru_stime.tv_sec'] ?? 0) * 1e6 + ($rusage['ru_stime.tv_usec'] ?? 0),
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Calculate resource delta between two snapshots.
     */
    public static function delta(array $start, array $end): array
    {
        return [
            'memory_start' => $start['memory'],
            'memory_peak' => $end['memory_peak'],
            'memory_delta' => $end['memory'] - $start['memory'],
            'cpu_user_time_us' => $end['cpu_user_time'] - $start['cpu_user_time'],
            'cpu_system_time_us' => $end['cpu_system_time'] - $start['cpu_system_time'],
            'cpu_total_time_us' => ($end['cpu_user_time'] - $start['cpu_user_time']) + ($end['cpu_system_time'] - $start['cpu_system_time']),
        ];
    }
}
