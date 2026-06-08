<?php

declare(strict_types=1);

namespace Gfm\Tests\Unit\Security;

use Gfm\Security\MaintenanceGuard;
use Gfm\Support\Config;
use PHPUnit\Framework\TestCase;

final class MaintenanceGuardTest extends TestCase
{
    private ?string $savedKey = null;

    protected function setUp(): void
    {
        $this->savedKey = getenv('MAINTENANCE_API_KEY') !== false ? (string) getenv('MAINTENANCE_API_KEY') : null;
        putenv('MAINTENANCE_API_KEY=test-maintenance-secret');
        $_ENV['MAINTENANCE_API_KEY'] = 'test-maintenance-secret';
        Config::resetCache();
    }

    protected function tearDown(): void
    {
        if ($this->savedKey === null) {
            putenv('MAINTENANCE_API_KEY');
            unset($_ENV['MAINTENANCE_API_KEY']);
        } else {
            putenv('MAINTENANCE_API_KEY=' . $this->savedKey);
            $_ENV['MAINTENANCE_API_KEY'] = $this->savedKey;
        }
        Config::resetCache();
    }

    public function testIsValidKeyAcceptsConfiguredSecret(): void
    {
        self::assertTrue(MaintenanceGuard::isValidKey('test-maintenance-secret'));
        self::assertFalse(MaintenanceGuard::isValidKey('wrong-key'));
    }

    public function testApiKeyReadsFromConfig(): void
    {
        self::assertSame('test-maintenance-secret', MaintenanceGuard::apiKey());
    }
}
