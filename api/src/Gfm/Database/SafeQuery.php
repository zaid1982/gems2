<?php

declare(strict_types=1);

namespace Gfm\Database;

use Gfm\Support\Config;
use PDO;
use PDOStatement;

/**
 * Thin wrapper around a PDO connection that runs ONLY parameterized queries.
 *
 * This is the target API for new and migrated endpoints, replacing the legacy
 * string-concatenation builders. Values are always bound; identifiers (for the
 * convenience insert/update helpers) are validated via Identifier.
 *
 * Obtain the live connection from the surrounding stack so the query joins the
 * same transaction, e.g.:
 *   new SafeQuery(Class_db::getInstance()->getPdo())   // legacy procedural stack
 *   new SafeQuery(DbMysql::$DBH)                        // OOP stack
 */
final class SafeQuery
{
    /** Queries slower than this (ms) are logged when APP_DEBUG is on. */
    private const float SLOW_QUERY_MS = 200.0;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function select(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function selectOne(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed Single scalar value from the first column, or null.
     */
    public function scalar(string $sql, array $params = []): mixed
    {
        $value = $this->run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    /**
     * @param array<string, mixed> $params
     * @return int Number of affected rows.
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    /**
     * Build and run a parameterized INSERT from a column => value map.
     *
     * @param array<string, mixed> $data
     * @return string Last insert id.
     */
    public function insert(string $table, array $data): string
    {
        $columns = array_keys($data);
        $colSql = implode(', ', array_map([Identifier::class, 'quote'], $columns));
        $placeholders = implode(', ', array_map(static fn (string $c): string => ':' . $c, $columns));

        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }

        $this->run('INSERT INTO ' . Identifier::quote($table) . ' (' . $colSql . ') VALUES (' . $placeholders . ')', $params);
        $id = $this->pdo->lastInsertId();

        return $id === false ? '' : $id;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function run(string $sql, array $params): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $start = microtime(true);
        $stmt->execute($params);
        $elapsedMs = (microtime(true) - $start) * 1000.0;

        if ($elapsedMs >= self::SLOW_QUERY_MS && Config::isDebug()) {
            error_log(sprintf('[gfm] slow query %.1fms: %s', $elapsedMs, (string) preg_replace('/\s+/', ' ', trim($sql))));
        }

        return $stmt;
    }
}
