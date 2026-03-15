<?php

namespace AnimaID\Exceptions;

/**
 * Forbidden Exception
 * Thrown when an authenticated user lacks permission to perform an action
 */
class ForbiddenException extends \RuntimeException
{
    protected int $httpCode = 403;

    public function __construct(string $message = 'Insufficient permissions', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}
