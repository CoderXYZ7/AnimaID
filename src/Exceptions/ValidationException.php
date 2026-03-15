<?php

namespace AnimaID\Exceptions;

/**
 * Validation Exception
 * Thrown when request data fails validation
 */
class ValidationException extends \RuntimeException
{
    protected int $httpCode = 422;

    public function __construct(string $message = 'Validation failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}
