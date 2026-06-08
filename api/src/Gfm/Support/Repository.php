<?php

declare(strict_types=1);

namespace Gfm\Support;

use Gfm\Database\Connection;
use Gfm\Database\SafeQuery;

/**
 * Base class for domain repositories carved out of the legacy "god" function
 * files (f_wo.php, f_ppm.php, ...).
 *
 * A repository owns the SQL for one cohesive slice of a domain and exposes
 * intention-revealing, parameterized methods via SafeQuery. By default it binds
 * to the shared request connection, so a repository used inside a legacy
 * request participates in the same transaction.
 */
abstract class Repository
{
    protected SafeQuery $db;

    public function __construct(?SafeQuery $db = null)
    {
        $this->db = $db ?? new SafeQuery(Connection::shared());
    }
}
