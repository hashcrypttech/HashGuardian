<?php

namespace Hashcrypttech\HashGuardian\Contracts;

use Illuminate\Support\Collection;
use Hashcrypttech\HashGuardian\EntryType;

interface EntriesRepository
{
    /**
     * Store an array of IncomingEntry objects.
     */
    public function store(array $entries): void;

    /**
     * Find an entry by UUID.
     */
    public function find(string $uuid): ?object;

    /**
     * Get entries by type with optional filtering.
     */
    public function get(string $type, array $options = []): Collection;

    /**
     * Get entries sharing the same batch ID.
     */
    public function getByBatchId(string $batchId): Collection;

    /**
     * Get aggregated stats for a given entry type.
     */
    public function getStats(string $type, array $options = []): array;

    /**
     * Get counts per entry type for the dashboard.
     */
    public function getCounts(array $options = []): array;

    /**
     * Prune entries older than the given hours.
     */
    public function prune(int $hours): int;

    /**
     * Clear all entries.
     */
    public function clear(): void;
}
