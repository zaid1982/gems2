<?php

declare(strict_types=1);

namespace Gfm\Tests\Unit\Support;

use Gfm\Support\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $saved = [];

    private const KEYS = [
        'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_PORT',
        'APP_DEBUG', 'APP_ENV', 'JWT_SECRET', 'JWT_TTL', 'JWT_LEEWAY',
    ];

    protected function setUp(): void
    {
        foreach (self::KEYS as $key) {
            $this->saved[$key] = getenv($key);
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
        Config::resetCache();
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
    }

    public function testEnvironmentVariableTakesPrecedence(): void
    {
        putenv('DB_HOST=env-db-host');
        putenv('DB_NAME=env-db-name');
        putenv('DB_USER=env-user');
        putenv('DB_PASS=env-secret');
        putenv('DB_PORT=3307');

        self::assertSame('env-db-host', Config::dbHost());
        self::assertSame('env-db-name', Config::dbName());
        self::assertSame('env-user', Config::dbUser());
        self::assertSame('env-secret', Config::dbPassword());
        self::assertSame(3307, Config::dbPort());
    }

    public function testIsDebugParsesTruthyValues(): void
    {
        putenv('APP_DEBUG=true');
        self::assertTrue(Config::isDebug());

        putenv('APP_DEBUG=0');
        self::assertFalse(Config::isDebug());

        putenv('APP_DEBUG');
        self::assertFalse(Config::isDebug());
    }

    public function testJwtSecretFallsBackToLegacyKeyWhenUnset(): void
    {
        // Backward-compatibility guarantee: with no secret configured the
        // application must still validate previously issued tokens. The
        // fallback emits an operational warning via error_log, which we
        // redirect to a temp file so it does not pollute test output.
        $logFile = tempnam(sys_get_temp_dir(), 'gfmlog');
        $previous = ini_get('error_log');
        ini_set('error_log', (string) $logFile);
        try {
            self::assertSame('gems2', Config::jwtSecret());
        } finally {
            ini_set('error_log', $previous === false ? '' : $previous);
            if (is_string($logFile)) {
                @unlink($logFile);
            }
        }
    }

    public function testJwtSecretHonoursEnvironment(): void
    {
        putenv('JWT_SECRET=a-strong-random-secret');
        self::assertSame('a-strong-random-secret', Config::jwtSecret());
    }

    public function testJwtDefaultsPreserveHistoricalLifetime(): void
    {
        self::assertSame(86400, Config::jwtTtl());
        self::assertSame(86400, Config::jwtLeeway());
    }

    public function testSmtpReturnsExpectedShape(): void
    {
        $smtp = Config::smtp();
        foreach (['host', 'port', 'security', 'username', 'password', 'from', 'envelope_from', 'timeout'] as $key) {
            self::assertArrayHasKey($key, $smtp);
        }
        self::assertIsInt($smtp['port']);
        self::assertIsInt($smtp['timeout']);
    }
}
