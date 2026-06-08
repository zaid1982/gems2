<?php

declare(strict_types=1);

namespace Gfm\Tests\Unit\WorkOrder;

use Gfm\Database\SafeQuery;
use Gfm\WorkOrder\WorkOrderRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class WorkOrderRepositoryTest extends TestCase
{
    private WorkOrderRepository $repo;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite required.');
        }
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE wo_task (
                wo_task_id INTEGER PRIMARY KEY AUTOINCREMENT,
                wo_task_no TEXT,
                site_id INTEGER,
                wo_task_status INTEGER,
                wo_task_location TEXT,
                wo_task_time_created TEXT
            )'
        );
        $rows = [
            ['WO-1', 1, 24, 'Level 1', '2026-01-01 09:00:00'],
            ['WO-2', 1, 24, 'Level 2', '2026-01-02 09:00:00'],
            ['WO-3', 1, 17, 'Level 3', '2026-01-03 09:00:00'],
            ['WO-4', 2, 24, 'Block B', '2026-01-04 09:00:00'],
        ];
        $stmt = $pdo->prepare('INSERT INTO wo_task (wo_task_no, site_id, wo_task_status, wo_task_location, wo_task_time_created) VALUES (?,?,?,?,?)');
        foreach ($rows as $r) {
            $stmt->execute($r);
        }

        $this->repo = new WorkOrderRepository(new SafeQuery($pdo));
    }

    public function testFindById(): void
    {
        $row = $this->repo->findById('1');
        self::assertNotNull($row);
        self::assertSame('WO-1', $row['wo_task_no']);
    }

    public function testFindByIdMissingReturnsNull(): void
    {
        self::assertNull($this->repo->findById('999'));
    }

    public function testCountByStatusForSite(): void
    {
        $counts = $this->repo->countByStatusForSite('1');
        $map = [];
        foreach ($counts as $row) {
            $map[(int) $row['wo_task_status']] = (int) $row['total'];
        }
        self::assertSame(2, $map[24]);
        self::assertSame(1, $map[17]);
    }

    public function testListBySiteIsOrderedAndPaged(): void
    {
        $list = $this->repo->listBySite('1', 2, 0);
        self::assertCount(2, $list);
        // Newest first.
        self::assertSame('WO-3', $list[0]['wo_task_no']);
        self::assertSame('WO-2', $list[1]['wo_task_no']);
    }
}
