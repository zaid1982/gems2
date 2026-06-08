<?php

declare(strict_types=1);

namespace Gfm\Tests\Unit\Support;

use Gfm\Support\Env;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class EnvTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (['GFM_TEST_A', 'GFM_TEST_B', 'GFM_TEST_QUOTED', 'GFM_TEST_PREEXISTING'] as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
        $this->resetLoadedFlag();
    }

    public function testGetPrefersProcessEnvironment(): void
    {
        putenv('GFM_TEST_A=value-a');
        self::assertSame('value-a', Env::get('GFM_TEST_A'));
        self::assertSame('fallback', Env::get('GFM_TEST_MISSING', 'fallback'));
    }

    public function testLoadParsesFileAndDoesNotOverrideExistingValues(): void
    {
        putenv('GFM_TEST_PREEXISTING=keep-me');
        $tmp = tempnam(sys_get_temp_dir(), 'gfmenv');
        self::assertIsString($tmp);
        file_put_contents($tmp, implode("\n", [
            '# a comment',
            'GFM_TEST_A=loaded-a',
            'export GFM_TEST_B = loaded-b',
            'GFM_TEST_QUOTED="quoted value"',
            'GFM_TEST_PREEXISTING=should-not-win',
        ]));

        $this->resetLoadedFlag();
        Env::load($tmp);

        self::assertSame('loaded-a', Env::get('GFM_TEST_A'));
        self::assertSame('loaded-b', Env::get('GFM_TEST_B'));
        self::assertSame('quoted value', Env::get('GFM_TEST_QUOTED'));
        self::assertSame('keep-me', Env::get('GFM_TEST_PREEXISTING'));

        unlink($tmp);
    }

    private function resetLoadedFlag(): void
    {
        $ref = new ReflectionClass(Env::class);
        $prop = $ref->getProperty('loaded');
        $prop->setValue(null, false);
    }
}
