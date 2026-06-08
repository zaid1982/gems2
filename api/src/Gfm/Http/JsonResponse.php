<?php

declare(strict_types=1);

namespace Gfm\Http;

/**
 * The standard API response envelope used across every endpoint:
 *   { "success": bool, "result": mixed, "error": string, "errmsg": string }
 *
 * Formalizing it in one place keeps the contract identical for the Flutter app
 * and web UI while removing the copy-pasted `$form_data = array(...)` blocks.
 */
final class JsonResponse
{
    private function __construct(
        public bool $success,
        public mixed $result = '',
        public string $error = '',
        public string $errmsg = '',
    ) {
    }

    public static function success(mixed $result = '', string $errmsg = ''): self
    {
        return new self(true, $result, '', $errmsg);
    }

    public static function failure(string $error, string $errmsg): self
    {
        return new self(false, '', $error, $errmsg);
    }

    /** @return array{success:bool, result:mixed, error:string, errmsg:string} */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'result' => $this->result,
            'error' => $this->error,
            'errmsg' => $this->errmsg,
        ];
    }

    public function toJson(): string
    {
        return (string) json_encode($this->toArray());
    }

    public function send(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo $this->toJson();
    }
}
