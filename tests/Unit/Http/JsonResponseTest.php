<?php

declare(strict_types=1);

namespace Gfm\Tests\Unit\Http;

use Gfm\Http\JsonResponse;
use PHPUnit\Framework\TestCase;

final class JsonResponseTest extends TestCase
{
    public function testSuccessEnvelope(): void
    {
        $r = JsonResponse::success(['id' => 5], 'Saved');
        self::assertSame(
            ['success' => true, 'result' => ['id' => 5], 'error' => '', 'errmsg' => 'Saved'],
            $r->toArray()
        );
    }

    public function testFailureEnvelope(): void
    {
        $r = JsonResponse::failure('boom detail', 'Something went wrong');
        $arr = $r->toArray();
        self::assertFalse($arr['success']);
        self::assertSame('boom detail', $arr['error']);
        self::assertSame('Something went wrong', $arr['errmsg']);
        self::assertSame('', $arr['result']);
    }

    public function testEnvelopeKeysAndOrderAreStable(): void
    {
        $arr = JsonResponse::success()->toArray();
        self::assertSame(['success', 'result', 'error', 'errmsg'], array_keys($arr));
    }

    public function testToJsonIsValid(): void
    {
        $json = JsonResponse::success('ok')->toJson();
        $decoded = json_decode($json, true);
        self::assertIsArray($decoded);
        self::assertTrue($decoded['success']);
        self::assertSame('ok', $decoded['result']);
    }
}
