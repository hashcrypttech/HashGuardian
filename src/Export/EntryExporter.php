<?php

namespace Hashcrypttech\HashGuardian\Export;

use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;

class EntryExporter
{
    protected EntriesRepository $repository;

    public function __construct(EntriesRepository $repository)
    {
        $this->repository = $repository;
    }

    public function export(string $type, array $filters = [], string $format = 'csv'): string
    {
        $filters['limit'] = $filters['limit'] ?? 1000;
        $entries = $this->repository->get($type, $filters);

        $rows = $entries->map(fn ($e) => [
            'uuid' => $e->uuid,
            'type' => $e->type,
            'status' => $e->status,
            'duration' => $e->duration,
            'content' => is_array($e->content) ? json_encode($e->content) : $e->content,
            'created_at' => $e->created_at,
        ])->toArray();

        $manager = new ExportManager();

        return match ($format) {
            'json' => $manager->toJson($rows),
            default => $manager->toCsv($rows),
        };
    }
}
