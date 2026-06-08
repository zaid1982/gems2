<?php

declare(strict_types=1);

namespace Gfm\Security;

use Firebase\JWT\JWT;
use Gfm\Support\Config;

/**
 * Centralized JWT issuing/validation.
 *
 * Replaces the previous hardcoded secret ("gems2") and the
 * "exp = now + 10s, leeway = 86400" hack with values resolved from Config
 * (JWT_SECRET / JWT_TTL / JWT_LEEWAY). Defaults are backward compatible so
 * tokens issued by the old implementation continue to validate during rollout.
 *
 * Token shape is preserved (iss/userId/username/iat/exp) so existing clients
 * keep working; a "type" claim is added to distinguish access vs refresh
 * tokens (older tokens without it are treated as access tokens).
 */
final class TokenService
{
    private const ISSUER = 'gems2/jwt';
    private const ALG = 'HS256';

    public const TYPE_ACCESS = 'access';
    public const TYPE_REFRESH = 'refresh';

    public static function issueAccessToken(string $userId, string $username): string
    {
        return self::encode($userId, $username, self::TYPE_ACCESS, Config::jwtTtl());
    }

    public static function issueRefreshToken(string $userId, string $username): string
    {
        return self::encode($userId, $username, self::TYPE_REFRESH, Config::jwtRefreshTtl());
    }

    /**
     * Decode a raw token or an "Authorization: Bearer <token>" header value.
     *
     * @return object Decoded payload (stdClass)
     */
    public static function decode(string $jwt): object
    {
        $token = self::stripBearer($jwt);
        JWT::$leeway = Config::jwtLeeway();

        $decoded = JWT::decode($token, Config::jwtSecret(), [self::ALG]);
        if (!is_object($decoded)) {
            throw new \Exception('Decoded token payload is not an object');
        }

        return $decoded;
    }

    /**
     * Decode and require the token to be a refresh token.
     *
     * @return object Decoded payload (stdClass)
     * @throws \Exception when the token is not a refresh token
     */
    public static function decodeRefresh(string $jwt): object
    {
        $data = self::decode($jwt);
        $type = $data->type ?? self::TYPE_ACCESS;
        if ($type !== self::TYPE_REFRESH) {
            throw new \Exception('Provided token is not a refresh token');
        }

        return $data;
    }

    public static function stripBearer(string $jwt): string
    {
        $jwt = trim($jwt);
        if (stripos($jwt, 'Bearer ') === 0) {
            return substr($jwt, 7);
        }

        return $jwt;
    }

    private static function encode(string $userId, string $username, string $type, int $ttl): string
    {
        $now = time();
        $payload = [
            'iss' => self::ISSUER,
            'userId' => $userId,
            'username' => $username,
            'type' => $type,
            'iat' => $now,
            'exp' => $now + $ttl,
        ];

        return JWT::encode($payload, Config::jwtSecret());
    }
}
