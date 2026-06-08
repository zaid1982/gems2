<?php

declare(strict_types=1);

namespace Gfm\Tests\Unit\Http;

use Gfm\Domain\ErrorCode;
use Gfm\Http\ApiException;
use PHPUnit\Framework\TestCase;

final class ApiExceptionTest extends TestCase
{
    public function testUserFacingCarriesMessageAndCode(): void
    {
        $ex = ApiException::userFacing('Invalid login');
        self::assertSame('Invalid login', $ex->getMessage());
        self::assertSame('Invalid login', $ex->userMessage());
        self::assertTrue($ex->hasUserMessage());
        self::assertSame(ErrorCode::USER_FACING, $ex->getCode());
    }

    public function testInternalHidesUserMessage(): void
    {
        $ex = ApiException::internal('stack-trace-ish detail');
        self::assertSame('stack-trace-ish detail', $ex->getMessage());
        self::assertSame('', $ex->userMessage());
        self::assertFalse($ex->hasUserMessage());
        self::assertSame(ErrorCode::INTERNAL, $ex->getCode());
    }

    public function testErrorCodeClassification(): void
    {
        self::assertTrue(ErrorCode::isUserFacing(ErrorCode::USER_FACING));
        self::assertTrue(ErrorCode::isUserFacing(ErrorCode::BLOCKED));
        self::assertFalse(ErrorCode::isUserFacing(ErrorCode::INTERNAL));
    }
}
