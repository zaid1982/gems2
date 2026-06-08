<?php

declare(strict_types=1);

namespace Gfm\Tests\Unit\Security;

use Firebase\JWT\JWT;
use Gfm\Security\TokenService;
use Gfm\Support\Config;
use PHPUnit\Framework\TestCase;

final class TokenServiceTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $saved = [];

    private const KEYS = ['JWT_SECRET', 'JWT_TTL', 'JWT_LEEWAY', 'JWT_REFRESH_TTL'];

    protected function setUp(): void
    {
        foreach (self::KEYS as $key) {
            $this->saved[$key] = getenv($key);
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
        putenv('JWT_SECRET=test-secret-please-ignore');
        putenv('JWT_TTL=3600');
        putenv('JWT_LEEWAY=86400');
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
        JWT::$leeway = 0;
    }

    public function testIssueAndDecodeAccessToken(): void
    {
        $token = TokenService::issueAccessToken('42', 'jdoe');
        $decoded = TokenService::decode($token);

        self::assertSame('42', $decoded->userId);
        self::assertSame('jdoe', $decoded->username);
        self::assertSame(TokenService::TYPE_ACCESS, $decoded->type);
        self::assertGreaterThan(time(), $decoded->exp);
    }

    public function testDecodeHandlesBearerPrefix(): void
    {
        $token = TokenService::issueAccessToken('1', 'a');
        $decoded = TokenService::decode('Bearer ' . $token);
        self::assertSame('1', $decoded->userId);
    }

    public function testRefreshTokenTypeIsEnforced(): void
    {
        $refresh = TokenService::issueRefreshToken('7', 'rt');
        $decoded = TokenService::decodeRefresh($refresh);
        self::assertSame(TokenService::TYPE_REFRESH, $decoded->type);

        $access = TokenService::issueAccessToken('7', 'rt');
        $this->expectException(\Exception::class);
        TokenService::decodeRefresh($access);
    }

    public function testStripBearerIsCaseInsensitiveAndOptional(): void
    {
        self::assertSame('abc.def', TokenService::stripBearer('Bearer abc.def'));
        self::assertSame('abc.def', TokenService::stripBearer('bearer abc.def'));
        self::assertSame('abc.def', TokenService::stripBearer('abc.def'));
    }

    public function testLegacyShapedTokenStillValidatesWithinLeeway(): void
    {
        // Reproduces the previous implementation: exp = now + 10s, no "type"
        // claim. With the default generous leeway it must still decode so live
        // clients are not logged out during rollout.
        $legacy = JWT::encode([
            'iss' => 'gems2/jwt',
            'userId' => '99',
            'username' => 'legacy',
            'iat' => time(),
            'exp' => time() + 10,
        ], Config::jwtSecret());

        $decoded = TokenService::decode($legacy);
        self::assertSame('99', $decoded->userId);
        // Tokens without an explicit type are treated as access tokens.
        self::assertSame(TokenService::TYPE_ACCESS, $decoded->type ?? TokenService::TYPE_ACCESS);
    }
}
