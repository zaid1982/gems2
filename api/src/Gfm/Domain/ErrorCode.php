<?php

declare(strict_types=1);

namespace Gfm\Domain;

/**
 * Exception codes that the API layer treats specially.
 *
 * These mirror the bare integers used throughout the legacy endpoints
 * (`$ex->getCode() === 31`, `... === 32`, throwEmpty = 2 -> code 30). Naming
 * them removes the magic numbers and documents intent. Kept as typed constants
 * (not a backed enum) because they are compared directly against the int
 * returned by Exception::getCode().
 */
final class ErrorCode
{
    /** Internal/unexpected error: the user sees only the generic message. */
    public const int INTERNAL = 0;

    /** Requested record not found / empty result that should surface to the user. */
    public const int NOT_FOUND = 30;

    /** Validation/business/authentication error whose message is safe to show. */
    public const int USER_FACING = 31;

    /** Account blocked / wrong-password lockout (login flow). */
    public const int BLOCKED = 32;

    /** True when an exception code's message should be shown to the user. */
    public static function isUserFacing(int $code): bool
    {
        return in_array($code, [self::NOT_FOUND, self::USER_FACING, self::BLOCKED], true);
    }
}
