<?php

declare(strict_types=1);

namespace Gfm\Tests\Unit\Database;

use Gfm\Database\Identifier;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IdentifierTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function validNames(): array
    {
        return [
            'simple' => ['users'],
            'underscore' => ['sys_user'],
            'qualified' => ['sys_user.user_id'],
            'backticked' => ['`user_id`'],
            'qualified backticked' => ['`t`.`c`'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidNames(): array
    {
        return [
            'leading digit' => ['1col'],
            'semicolon' => ['col; DROP TABLE x'],
            'quote' => ["col'"],
            'dash' => ['col-name'],
            'space' => ['col name'],
            'empty' => [''],
            'paren' => ['col)'],
        ];
    }

    #[DataProvider('validNames')]
    public function testValidIdentifiers(string $name): void
    {
        self::assertTrue(Identifier::isValid($name));
        self::assertSame($name, Identifier::assertValid($name));
    }

    #[DataProvider('invalidNames')]
    public function testInvalidIdentifiers(string $name): void
    {
        self::assertFalse(Identifier::isValid($name));
        $this->expectException(InvalidArgumentException::class);
        Identifier::assertValid($name);
    }

    public function testQuoteWrapsParts(): void
    {
        self::assertSame('`sys_user`', Identifier::quote('sys_user'));
        self::assertSame('`t`.`c`', Identifier::quote('t.c'));
        self::assertSame('`already`', Identifier::quote('`already`'));
    }
}
