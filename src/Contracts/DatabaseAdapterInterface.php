<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Contracts;

interface DatabaseAdapterInterface
{
    /**
     * Insert a row into table and return last insert ID.
     */
    public function insert(string $table, array $data): string|int;

    /**
     * Fetch a single row as an object.
     */
    public function fetchWaitingJob(string $table, string $queue, array $priorities = []): ?object;

    /**
     * Update rows matching condition.
     */
    public function update(string $table, array $data, array $where): bool;

    /**
     * Delete rows matching condition.
     */
    public function delete(string $table, array $where): int;

    /**
     * Fetch failed jobs.
     */
    public function fetchFailed(string $table, ?string $queue = null, ?int $id = null): array;
}
