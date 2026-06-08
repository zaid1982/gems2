<?php

declare(strict_types=1);

namespace Gfm\Database;

use InvalidArgumentException;

/**
 * Validates and safely quotes SQL identifiers (table/column names).
 *
 * Identifiers can never be bound as query parameters, so any identifier that
 * originates from input must be validated against a strict allowlist before
 * being interpolated. Use this instead of concatenating raw names.
 */
final class Identifier
{
    public static function isValid(string $name): bool
    {
        return preg_match('/^`?[A-Za-z_][A-Za-z0-9_]*`?(\.`?[A-Za-z_][A-Za-z0-9_]*`?)?$/', $name) === 1;
    }

    public static function assertValid(string $name): string
    {
        if (!self::isValid($name)) {
            throw new InvalidArgumentException('Invalid SQL identifier: ' . $name);
        }

        return $name;
    }

    /** Return a backtick-quoted identifier, e.g. `table`.`column`. */
    public static function quote(string $name): string
    {
        self::assertValid($name);
        $parts = explode('.', str_replace('`', '', $name));
        $quoted = array_map(static fn (string $p): string => '`' . $p . '`', $parts);

        return implode('.', $quoted);
    }
}
