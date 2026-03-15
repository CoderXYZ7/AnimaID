<?php

namespace AnimaID\Exceptions;

/**
 * Not Found Exception
 * Thrown when a requested resource does not exist
 */
class NotFoundException extends \RuntimeException
{
    protected int $httpCode = 404;

    public function __construct(string $message = 'Resource not found', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}
