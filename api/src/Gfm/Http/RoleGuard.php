<?php

declare(strict_types=1);

namespace Gfm\Http;

use Exception;

/**
 * Server-side role enforcement.
 *
 * The web UI hides actions a user may not perform, but authorization must also
 * be enforced on the server. Endpoints should load the caller's roles (e.g. the
 * `vw_roles` view used at login) and gate privileged actions with this guard
 * instead of relying on the client.
 *
 * Roles are expected in the shape returned by the login flow:
 *   [ ['roleId' => '3', 'roleDesc' => 'Admin', ...], ... ]
 */
final class RoleGuard
{
    /**
     * @param array<int, array<string, mixed>> $roles
     * @return array<int, string>
     */
    public static function roleIds(array $roles): array
    {
        $ids = [];
        foreach ($roles as $role) {
            if (isset($role['roleId'])) {
                $ids[] = (string) $role['roleId'];
            }
        }

        return $ids;
    }

    /**
     * @param array<int, array<string, mixed>> $roles
     */
    public static function has(array $roles, string|int $roleId): bool
    {
        return in_array((string) $roleId, self::roleIds($roles), true);
    }

    /**
     * Require the caller to hold at least one of the allowed roles.
     *
     * @param array<int, array<string, mixed>> $roles
     * @param array<int, string|int> $allowed
     * @throws Exception when the caller holds none of the allowed roles
     */
    public static function requireAny(array $roles, array $allowed): void
    {
        foreach ($allowed as $roleId) {
            if (self::has($roles, $roleId)) {
                return;
            }
        }

        throw new Exception('You are not allowed to perform this operation');
    }
}
