<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\VarDumper;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class DumpWatcher extends Watcher
{
    public function register(Application $app): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $alwaysActive = $this->option('always', false);
        $cacheActive = false;

        try {
            $cacheActive = $app['cache']->get('hashguardian:dump-watcher');
        } catch (\Throwable $e) {
        }

        if (! $alwaysActive && ! $cacheActive) {
            return;
        }

        $htmlDumper = new HtmlDumper();
        $htmlDumper->setDumpHeader('');

        VarDumper::setHandler(function ($var) use ($htmlDumper) {
            $caller = $this->getCallerInfo();

            $entry = IncomingEntry::make(EntryType::DUMP, [
                'dump' => $htmlDumper->dump(
                    (new VarCloner())->cloneVar($var), true
                ),
                'file' => $caller['file'] ?? null,
                'line' => $caller['line'] ?? null,
            ]);

            $entry->familyHash(md5(($caller['file'] ?? '') . ':' . ($caller['line'] ?? '')));

            $this->record($entry);
        });
    }

    protected function getCallerInfo(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);

        foreach ($trace as $frame) {
            $file = $frame['file'] ?? '';

            if (str_contains($file, '/vendor/')) {
                continue;
            }
            if (str_contains($file, '/packages/webz/hashguardian/')) {
                continue;
            }

            if (str_contains($file, '/app/') || str_contains($file, '/routes/') || str_contains($file, '/resources/')) {
                return [
                    'file' => $file,
                    'line' => $frame['line'] ?? null,
                ];
            }
        }

        return [];
    }
}
