<?php

declare(strict_types=1);

namespace Gfm\WorkOrder;

use Gfm\Support\Repository;

/**
 * Reads for the Work Order domain.
 *
 * This is the first slice carved out of the ~200KB Class_wo (f_wo.php) to
 * demonstrate the target shape: cohesive, parameterized, unit-testable. The
 * legacy dashboard/report methods build these same queries via string
 * concatenation; they can be migrated to call this repository incrementally.
 *
 * See docs/REFACTOR_GODFILES.md for the full decomposition map.
 */
final class WorkOrderRepository extends Repository
{
    /**
     * @return array<string, mixed>|null
     */
    public function findById(string $woTaskId): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM wo_task WHERE wo_task_id = :id',
            [':id' => $woTaskId]
        );
    }

    /**
     * Count work orders grouped by status for a site (replaces an inline,
     * string-built dashboard query).
     *
     * @return array<int, array<string, mixed>>
     */
    public function countByStatusForSite(string $siteId): array
    {
        return $this->db->select(
            'SELECT wo_task_status, COUNT(*) AS total
               FROM wo_task
              WHERE site_id = :site
           GROUP BY wo_task_status',
            [':site' => $siteId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listBySite(string $siteId, int $limit = 20, int $offset = 0): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        return $this->db->select(
            'SELECT wo_task_id, wo_task_no, wo_task_location, wo_task_status, wo_task_time_created
               FROM wo_task
              WHERE site_id = :site
           ORDER BY wo_task_time_created DESC
              LIMIT ' . $limit . ' OFFSET ' . $offset,
            [':site' => $siteId]
        );
    }
}
