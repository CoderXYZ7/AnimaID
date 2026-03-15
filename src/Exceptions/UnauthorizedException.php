<?php

namespace AnimaID\Exceptions;

/**
 * Unauthorized Exception
 * Thrown when a request requires authentication that is missing or invalid
 */
class UnauthorizedException extends \RuntimeException
{
    protected int $httpCode = 401;

    public function __construct(string $message = 'Authentication required', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}
