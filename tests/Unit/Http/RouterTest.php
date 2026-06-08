<?php

declare(strict_types=1);

namespace Gfm\Tests\Unit\Http;

use Gfm\Http\ApiException;
use Gfm\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testDispatchReturnsHandlerResult(): void
    {
        $router = new Router();
        $router->get('/health', static fn (): string => 'ok');

        self::assertSame('ok', $router->dispatch('GET', '/health'));
    }

    public function testPathAndMethodAreNormalized(): void
    {
        $router = new Router();
        $router->post('/work-orders', static fn (): int => 1);

        self::assertSame(1, $router->dispatch('post', '/work-orders/'));
        self::assertTrue($router->has('POST', 'work-orders'));
    }

    public function testHandlerReceivesArgs(): void
    {
        $router = new Router();
        $router->get('/echo', static fn (array $args): mixed => $args['v'] ?? null);

        self::assertSame('hi', $router->dispatch('GET', '/echo', ['v' => 'hi']));
    }

    public function testUnknownRouteThrows(): void
    {
        $router = new Router();
        $this->expectException(ApiException::class);
        $router->dispatch('GET', '/nope');
    }
}
