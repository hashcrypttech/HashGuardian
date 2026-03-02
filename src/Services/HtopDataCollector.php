<?php

namespace Hashcrypttech\HashGuardian\Services;

class HtopDataCollector
{
    public function collect(): array
    {
        return [
            'cpu' => $this->getCpuTicks(),
            'memory' => $this->getMemoryInfo(),
            'system' => $this->getSystemInfo(),
            'processes' => $this->getProcessList(),
            'tree' => $this->getProcessTree(),
        ];
    }

    protected function getCpuTicks(): array
    {
        $stat = @file_get_contents('/proc/stat');
        if (!$stat) {
            return ['cores' => [], 'total' => null];
        }

        $lines = explode("\n", trim($stat));
        $cores = [];
        $total = null;

        foreach ($lines as $line) {
            if (!preg_match('/^cpu(\d*)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $line, $m)) {
                continue;
            }

            $entry = [
                'user'    => (int) $m[2],
                'nice'    => (int) $m[3],
                'system'  => (int) $m[4],
                'idle'    => (int) $m[5],
                'iowait'  => (int) $m[6],
                'irq'     => (int) $m[7],
                'softirq' => (int) $m[8],
            ];

            if ($m[1] === '') {
                $total = $entry;
            } else {
                $cores[(int) $m[1]] = $entry;
            }
        }

        return ['cores' => $cores, 'total' => $total];
    }

    protected function getMemoryInfo(): array
    {
        $meminfo = @file_get_contents('/proc/meminfo');
        if (!$meminfo) {
            return [];
        }

        $data = [];
        $keys = ['MemTotal', 'MemFree', 'MemAvailable', 'Buffers', 'Cached', 'SwapTotal', 'SwapFree', 'SReclaimable'];

        foreach ($keys as $key) {
            if (preg_match('/' . $key . ':\s+(\d+)\s+kB/', $meminfo, $m)) {
                $data[$key] = (int) $m[1] * 1024;
            }
        }

        $total   = $data['MemTotal'] ?? 0;
        $free    = $data['MemFree'] ?? 0;
        $buffers = $data['Buffers'] ?? 0;
        $cached  = ($data['Cached'] ?? 0) + ($data['SReclaimable'] ?? 0);
        $used    = $total - $free - $buffers - $cached;

        return [
            'total'      => $total,
            'used'       => max(0, $used),
            'free'       => $free,
            'buffers'    => $buffers,
            'cached'     => $cached,
            'available'  => $data['MemAvailable'] ?? ($free + $buffers + $cached),
            'swap_total' => $data['SwapTotal'] ?? 0,
            'swap_free'  => $data['SwapFree'] ?? 0,
            'swap_used'  => ($data['SwapTotal'] ?? 0) - ($data['SwapFree'] ?? 0),
        ];
    }

    protected function getSystemInfo(): array
    {
        $loads = [0, 0, 0];
        $loadavg = @file_get_contents('/proc/loadavg');
        if ($loadavg && preg_match('/^([\d.]+)\s+([\d.]+)\s+([\d.]+)/', $loadavg, $m)) {
            $loads = [(float) $m[1], (float) $m[2], (float) $m[3]];
        }

        $uptime = 0;
        $uptimeFile = @file_get_contents('/proc/uptime');
        if ($uptimeFile && preg_match('/^([\d.]+)/', $uptimeFile, $m)) {
            $uptime = (int) $m[1];
        }

        $states = ['running' => 0, 'sleeping' => 0, 'stopped' => 0, 'zombie' => 0, 'total' => 0];
        $output = @shell_exec('ps -eo stat --no-headers 2>/dev/null') ?? '';
        foreach (explode("\n", trim($output)) as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $states['total']++;
            $s = $line[0];
            if ($s === 'R') $states['running']++;
            elseif (in_array($s, ['S', 'D', 'I'])) $states['sleeping']++;
            elseif (in_array($s, ['T', 't'])) $states['stopped']++;
            elseif ($s === 'Z') $states['zombie']++;
        }

        return [
            'load_avg' => $loads,
            'uptime'   => $uptime,
            'tasks'    => $states,
        ];
    }

    protected function getProcessList(): array
    {
        $swapMap = $this->getSwapMap();

        $output = @shell_exec('ps -eww -o pid,user:20,pri,ni,vsz,rss,stat,pcpu,pmem,cputime,args --sort=-%cpu --no-headers 2>/dev/null') ?? '';

        $processes = [];
        foreach (explode("\n", trim($output)) as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = preg_split('/\s+/', $line, 11);
            if (count($parts) < 11) continue;

            $pid = (int) $parts[0];

            $processes[] = [
                'pid'     => $pid,
                'user'    => $parts[1],
                'pri'     => (int) $parts[2],
                'ni'      => (int) $parts[3],
                'virt'    => (int) $parts[4] * 1024,
                'res'     => (int) $parts[5] * 1024,
                'swap'    => $swapMap[$pid] ?? 0,
                'state'   => $parts[6][0],
                'cpu'     => (float) $parts[7],
                'mem'     => (float) $parts[8],
                'time'    => $parts[9],
                'command' => $parts[10],
            ];
        }

        return $processes;
    }

    protected function getSwapMap(): array
    {
        $output = @shell_exec('grep VmSwap /proc/[0-9]*/status 2>/dev/null') ?? '';

        $map = [];
        foreach (explode("\n", trim($output)) as $line) {
            if (preg_match('#/proc/(\d+)/status:VmSwap:\s+(\d+)\s+kB#', $line, $m)) {
                $map[(int) $m[1]] = (int) $m[2] * 1024;
            }
        }

        return $map;
    }

    protected function getProcessTree(): array
    {
        $output = @shell_exec('ps -eo pid,ppid --no-headers 2>/dev/null') ?? '';

        $tree = [];
        foreach (explode("\n", trim($output)) as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 2) {
                $tree[(string) $parts[0]] = (int) $parts[1];
            }
        }

        return $tree;
    }
}
