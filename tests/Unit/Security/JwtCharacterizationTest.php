<?php

declare(strict_types=1);

namespace Gfm\Tests\Unit\Security;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;

/**
 * Characterization tests that pin the CURRENT behavior of the vendored
 * Firebase JWT library as used by Class_login. These exist so the JWT
 * hardening work (configurable secret/expiry) can be verified to preserve
 * round-trip compatibility for already-issued tokens.
 */
final class JwtCharacterizationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $src = dirname(__DIR__, 3) . '/api/src';
        foreach (['BeforeValidException.php', 'ExpiredException.php', 'SignatureInvalidException.php', 'JWT.php'] as $file) {
            require_once $src . '/' . $file;
        }
    }

    protected function tearDown(): void
    {
        JWT::$leeway = 0;
    }

    public function testEncodeDecodeRoundTrip(): void
    {
        $key = 'gems2';
        $payload = ['iss' => 'gems2/jwt', 'userId' => '42', 'username' => 'jdoe', 'iat' => time(), 'exp' => time() + 60];

        $token = JWT::encode($payload, $key);
        $decoded = JWT::decode($token, $key, ['HS256']);

        self::assertSame('42', $decoded->userId);
        self::assertSame('jdoe', $decoded->username);
    }

    public function testExpiredTokenIsRejectedWithoutLeeway(): void
    {
        $key = 'gems2';
        JWT::$leeway = 0;
        $payload = ['userId' => '1', 'username' => 'old', 'iat' => time() - 120, 'exp' => time() - 60];
        $token = JWT::encode($payload, $key);

        $this->expectException(ExpiredException::class);
        JWT::decode($token, $key, ['HS256']);
    }

    public function testGenerousLeewayKeepsShortLivedTokenValid(): void
    {
        // Mirrors the legacy behavior: exp = now + 10s but a large leeway keeps
        // the token usable. This is exactly what the hardening must preserve
        // during rollout so live clients are not logged out.
        $key = 'gems2';
        JWT::$leeway = 86400;
        $payload = ['userId' => '7', 'username' => 'live', 'iat' => time(), 'exp' => time() + 10];
        $token = JWT::encode($payload, $key);

        $decoded = JWT::decode($token, $key, ['HS256']);
        self::assertSame('7', $decoded->userId);
    }
}
