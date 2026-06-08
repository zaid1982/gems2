<?php

declare(strict_types=1);

namespace Gfm\Tests\Unit\Http;

use Gfm\Http\RoleGuard;
use PHPUnit\Framework\TestCase;

final class RoleGuardTest extends TestCase
{
    /** @return array<int, array<string, mixed>> */
    private function roles(): array
    {
        return [
            ['roleId' => '3', 'roleDesc' => 'Admin'],
            ['roleId' => 7, 'roleDesc' => 'Supervisor'],
        ];
    }

    public function testHasMatchesRegardlessOfType(): void
    {
        self::assertTrue(RoleGuard::has($this->roles(), '3'));
        self::assertTrue(RoleGuard::has($this->roles(), 3));
        self::assertTrue(RoleGuard::has($this->roles(), 7));
        self::assertFalse(RoleGuard::has($this->roles(), 99));
    }

    public function testRequireAnyPassesWhenRolePresent(): void
    {
        RoleGuard::requireAny($this->roles(), [99, 3]);
        $this->addToAssertionCount(1);
    }

    public function testRequireAnyThrowsWhenNoRoleMatches(): void
    {
        $this->expectException(\Exception::class);
        RoleGuard::requireAny($this->roles(), [99, 100]);
    }

    public function testRoleIdsExtractsStringIds(): void
    {
        self::assertSame(['3', '7'], RoleGuard::roleIds($this->roles()));
    }
}
