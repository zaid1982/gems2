<?php

declare(strict_types=1);

namespace Gfm\Database;

use Gfm\Support\Config;
use PDO;

/**
 * Single source of truth for building a correctly-configured PDO connection.
 *
 * The project historically had two parallel DB layers (Class_db and DbMysql)
 * each constructing their own PDO with slightly different attributes. This
 * factory is the consolidation target: new and migrated code should obtain its
 * connection here (and run queries through SafeQuery), so there is one place
 * that knows how to connect.
 *
 * Modern, safe defaults are used: exceptions on, real prepared statements.
 */
final class Connection
{
    private static ?PDO $shared = null;

    /** Build the DSN from configuration. Pure + unit-testable. */
    public static function dsn(): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8',
            Config::dbHost(),
            Config::dbPort(),
            Config::dbName()
        );
    }

    public static function create(): PDO
    {
        $pdo = new PDO(self::dsn(), Config::dbUser(), Config::dbPassword(), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("SET SESSION sql_mode = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

        return $pdo;
    }

    /** Lazily-created shared connection for the routed (new) request path. */
    public static function shared(): PDO
    {
        return self::$shared ??= self::create();
    }

    /** Convenience: a SafeQuery bound to the shared connection. */
    public static function query(): SafeQuery
    {
        return new SafeQuery(self::shared());
    }

    /** Test seam / allows binding to an existing legacy handle. */
    public static function setShared(?PDO $pdo): void
    {
        self::$shared = $pdo;
    }
}
