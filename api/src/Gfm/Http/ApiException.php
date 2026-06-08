<?php

declare(strict_types=1);

namespace Gfm\Http;

use Exception;
use Gfm\Domain\ErrorCode;
use Throwable;

/**
 * Exception that carries a separate user-facing message.
 *
 * Mirrors the legacy convention where exceptions with code 30/31/32 surface
 * their message to the user (as `errmsg`) while all other errors fall back to a
 * generic message. The central handler reads userMessage() to build the
 * envelope, so endpoints no longer need to re-implement that substring logic.
 */
final class ApiException extends Exception
{
    private string $userMessage;

    public function __construct(string $debugMessage, string $userMessage = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($debugMessage, $code, $previous);
        $this->userMessage = $userMessage;
    }

    /** A validation/business error whose message is safe to show the user. */
    public static function userFacing(string $message, int $code = ErrorCode::USER_FACING): self
    {
        return new self($message, $message, $code);
    }

    /** An internal error; the user sees only the generic message. */
    public static function internal(string $debugMessage, ?Throwable $previous = null): self
    {
        return new self($debugMessage, '', 0, $previous);
    }

    public function userMessage(): string
    {
        return $this->userMessage;
    }

    public function hasUserMessage(): bool
    {
        return $this->userMessage !== '';
    }
}
