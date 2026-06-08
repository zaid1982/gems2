<?php

declare(strict_types=1);

namespace Gfm\Tests\Unit\Database;

use Gfm\Database\SafeQuery;
use PDO;
use PHPUnit\Framework\TestCase;

final class SafeQueryTest extends TestCase
{
    private PDO $pdo;
    private SafeQuery $db;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required for SafeQuery tests.');
        }
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE t (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $this->db = new SafeQuery($this->pdo);
    }

    public function testInsertSelectScalar(): void
    {
        $id = $this->db->insert('t', ['name' => 'alice']);
        self::assertSame('1', $id);

        $row = $this->db->selectOne('SELECT * FROM t WHERE id = :id', [':id' => $id]);
        self::assertNotNull($row);
        self::assertSame('alice', $row['name']);

        $count = $this->db->scalar('SELECT COUNT(*) FROM t');
        self::assertSame(1, (int) $count);
    }

    public function testExecuteReturnsAffectedRows(): void
    {
        $this->db->insert('t', ['name' => 'a']);
        $this->db->insert('t', ['name' => 'b']);
        $affected = $this->db->execute('UPDATE t SET name = :n WHERE name = :old', [':n' => 'c', ':old' => 'a']);
        self::assertSame(1, $affected);
    }

    public function testBoundParameterDefeatsInjection(): void
    {
        // A classic injection payload must be stored as literal data, not executed.
        $payload = "x'); DROP TABLE t;--";
        $this->db->insert('t', ['name' => $payload]);

        // Table still exists and the payload was stored verbatim.
        $row = $this->db->selectOne('SELECT name FROM t WHERE name = :n', [':n' => $payload]);
        self::assertNotNull($row);
        self::assertSame($payload, $row['name']);
        self::assertSame(1, (int) $this->db->scalar('SELECT COUNT(*) FROM t'));
    }
}
