<?php

declare(strict_types=1);

namespace Gfm\Tests\Smoke;

use PHPUnit\Framework\TestCase;

/**
 * HTTP smoke tests that pin the public API contract for the highest-risk
 * endpoints (login, work orders, PPM). They run only when a target server is
 * configured, so they stay green in CI but provide a real safety net when run
 * against a staging deployment:
 *
 *   GFM_SMOKE_BASE_URL=https://staging.example/api/ \
 *   GFM_SMOKE_USER=alice GFM_SMOKE_PASS=secret \
 *   vendor/bin/phpunit --testsuite smoke
 */
final class ApiSmokeTest extends TestCase
{
    private string $baseUrl = '';

    protected function setUp(): void
    {
        $base = getenv('GFM_SMOKE_BASE_URL');
        if ($base === false || $base === '') {
            $this->markTestSkipped('Set GFM_SMOKE_BASE_URL to run API smoke tests.');
        }
        $this->baseUrl = rtrim($base, '/') . '/';
    }

    public function testLoginReturnsEnvelopeAndToken(): void
    {
        $user = getenv('GFM_SMOKE_USER');
        $pass = getenv('GFM_SMOKE_PASS');
        if ($user === false || $pass === false) {
            $this->markTestSkipped('Set GFM_SMOKE_USER and GFM_SMOKE_PASS to test login.');
        }

        $response = $this->post('login.php', ['action' => 'login_web', 'username' => $user, 'password' => $pass]);
        $this->assertEnvelope($response);
        self::assertTrue($response['success'], 'Login should succeed for valid credentials');
        self::assertArrayHasKey('token', $response['result']);
    }

    public function testUnknownGetTypeReturnsErrorEnvelope(): void
    {
        // Even an invalid request must return the stable JSON envelope shape
        // rather than an HTML error page.
        $response = $this->get('wo.php', ['type' => '__definitely_not_a_real_type__']);
        $this->assertEnvelope($response);
        self::assertFalse($response['success']);
    }

    /**
     * @param array<string, string> $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query): array
    {
        $url = $this->baseUrl . $path . '?' . http_build_query($query);
        $raw = @file_get_contents($url);
        self::assertIsString($raw, 'Request to ' . $path . ' returned no body');

        return $this->decode($raw);
    }

    /**
     * @param array<string, string> $fields
     * @return array<string, mixed>
     */
    private function post(string $path, array $fields): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($fields),
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($this->baseUrl . $path, false, $context);
        self::assertIsString($raw, 'Request to ' . $path . ' returned no body');

        return $this->decode($raw);
    }

    /** @return array<string, mixed> */
    private function decode(string $raw): array
    {
        $data = json_decode($raw, true);
        self::assertIsArray($data, 'Response was not valid JSON: ' . substr($raw, 0, 200));

        return $data;
    }

    /** @param array<string, mixed> $response */
    private function assertEnvelope(array $response): void
    {
        foreach (['success', 'result', 'error', 'errmsg'] as $key) {
            self::assertArrayHasKey($key, $response, 'Response envelope missing "' . $key . '"');
        }
        self::assertIsBool($response['success']);
    }
}
