<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Bus\Events\BatchDispatched;
use Illuminate\Contracts\Foundation\Application;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class BatchWatcher extends Watcher
{
    public function register(Application $app): void
    {
        $app['events']->listen(BatchDispatched::class, [$this, 'handleBatchDispatched']);
    }

    public function handleBatchDispatched(BatchDispatched $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $batchData = $event->batch->toArray();

        $entry = IncomingEntry::make(EntryType::BATCH, [
            'id' => $event->batch->id,
            'name' => $batchData['name'] ?? null,
            'total_jobs' => $batchData['totalJobs'] ?? 0,
            'pending_jobs' => $batchData['pendingJobs'] ?? 0,
            'failed_jobs' => $batchData['failedJobs'] ?? 0,
            'queue' => $event->batch->options['queue'] ?? 'default',
            'connection' => $event->batch->options['connection'] ?? 'default',
            'allows_failures' => $event->batch->allowsFailures(),
        ]);

        $entry->familyHash($event->batch->id);
        $entry->status('dispatched');
        $entry->tags([
            'batch:' . $event->batch->id,
            'queue:' . ($event->batch->options['queue'] ?? 'default'),
        ]);

        $this->record($entry);
    }
}
