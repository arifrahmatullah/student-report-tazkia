<?php

namespace App\Services\Siakad;

use RuntimeException;

class SiakadException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 0, public readonly ?string $errorCode = null)
    {
        parent::__construct($message, $status);
    }

    public function isNotFound(): bool
    {
        return $this->status === 404;
    }
}
