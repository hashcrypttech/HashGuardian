<?php

namespace Hashcrypttech\HashGuardian\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\Export\EntryExporter;

class ProcessExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $exportId,
        public string $type,
        public array $filters,
        public string $format,
    ) {}

    public function handle(EntriesRepository $repository): void
    {
        $exporter = new EntryExporter($repository);
        $content = $exporter->export($this->type, $this->filters, $this->format);

        $extension = $this->format === 'json' ? 'json' : 'csv';
        $filename = "hashguardian-exports/{$this->exportId}.{$extension}";

        Storage::put($filename, $content);
    }
}
