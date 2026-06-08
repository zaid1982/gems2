<?php

declare(strict_types=1);

namespace Gfm\Tests\Unit\Database;

use Gfm\Database\Connection;
use Gfm\Database\SafeQuery;
use Gfm\Support\Config;
use PDO;
use PHPUnit\Framework\TestCase;

final class ConnectionTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $saved = [];

    private const KEYS = ['DB_HOST', 'DB_PORT', 'DB_NAME'];

    protected function setUp(): void
    {
        foreach (self::KEYS as $key) {
            $this->saved[$key] = getenv($key);
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
        Config::resetCache();
        Connection::setShared(null);
    }

    protected function tearDown(): void
    {
        foreach ($this->saved as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
        Config::resetCache();
        Connection::setShared(null);
    }

    public function testDsnReflectsConfiguration(): void
    {
        putenv('DB_HOST=db.example');
        putenv('DB_PORT=3307');
        putenv('DB_NAME=mydb');
        Config::resetCache();

        self::assertSame('mysql:host=db.example;port=3307;dbname=mydb;charset=utf8', Connection::dsn());
    }

    public function testSharedConnectionIsReusedAndInjectable(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite required.');
        }
        $fake = new PDO('sqlite::memory:');
        Connection::setShared($fake);

        self::assertSame($fake, Connection::shared());
        self::assertInstanceOf(SafeQuery::class, Connection::query());
    }
}
