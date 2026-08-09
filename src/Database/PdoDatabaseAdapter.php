<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Database;

use Ttpryg\Queue\Contracts\DatabaseAdapterInterface;
use Ttpryg\Queue\Enums\Status;
use PDO;
use stdClass;

class PdoDatabaseAdapter implements DatabaseAdapterInterface
{
    public function __construct(
        protected PDO $pdo
    ) {
    }

    public function insert(string $table, array $data): string|int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return $this->pdo->lastInsertId();
    }

    public function fetchWaitingJob(string $table, string $queue, array $priorities = []): ?object
    {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM {$table} WHERE queue = :queue AND status = :status AND available_at <= :available_at";
        $params = [
            'queue' => $queue,
            'status' => Status::Waiting->value,
            'available_at' => $now,
        ];

        if (!empty($priorities)) {
            $inClause = [];
            foreach ($priorities as $idx => $pri) {
                $paramKey = 'pri_' . $idx;
                $inClause[] = ':' . $paramKey;
                $params[$paramKey] = $pri;
            }
            $sql .= ' AND priority IN (' . implode(',', $inClause) . ')';
        }

        $sql .= ' ORDER BY priority DESC, available_at ASC LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_OBJ);

        return $row ?: null;
    }

    public function update(string $table, array $data, array $where): bool
    {
        $setClauses = [];
        $params = [];

        foreach ($data as $col => $val) {
            $paramKey = 'set_' . $col;
            $setClauses[] = "{$col} = :{$paramKey}";
            $params[$paramKey] = $val;
        }

        $whereClauses = [];
        foreach ($where as $col => $val) {
            $paramKey = 'where_' . $col;
            $whereClauses[] = "{$col} = :{$paramKey}";
            $params[$paramKey] = $val;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $setClauses),
            implode(' AND ', $whereClauses)
        );

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(string $table, array $where): int
    {
        $whereClauses = [];
        $params = [];

        foreach ($where as $col => $val) {
            if (is_array($val)) {
                $inClause = [];
                foreach ($val as $idx => $v) {
                    $paramKey = "where_{$col}_{$idx}";
                    $inClause[] = ":{$paramKey}";
                    $params[$paramKey] = $v;
                }
                $whereClauses[] = "{$col} IN (" . implode(',', $inClause) . ')';
            } else {
                $paramKey = 'where_' . $col;
                $whereClauses[] = "{$col} = :{$paramKey}";
                $params[$paramKey] = $val;
            }
        }

        $sql = sprintf(
            'DELETE FROM %s WHERE %s',
            $table,
            implode(' AND ', $whereClauses)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    public function fetchFailed(string $table, ?string $queue = null, ?int $id = null): array
    {
        $sql = "SELECT * FROM {$table} WHERE status = :status";
        $params = ['status' => Status::Failed->value];

        if ($queue !== null) {
            $sql .= ' AND queue = :queue';
            $params['queue'] = $queue;
        }

        if ($id !== null) {
            $sql .= ' AND id = :id';
            $params['id'] = $id;
        }

        $sql .= ' ORDER BY updated_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
