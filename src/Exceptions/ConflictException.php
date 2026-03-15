<?php

namespace AnimaID\Exceptions;

/**
 * Conflict Exception
 * Thrown when a request conflicts with the current state of the resource
 */
class ConflictException extends \RuntimeException
{
    protected int $httpCode = 409;

    public function __construct(string $message = 'Resource conflict', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}
