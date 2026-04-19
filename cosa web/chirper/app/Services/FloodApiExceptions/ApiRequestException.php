<?php

declare(strict_types=1);

namespace App\Services\FloodApiExceptions;

class ApiRequestException extends \RuntimeException
{
    public int $status;

    /** @var array<string, mixed> */
    public array $payload;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(string $message, int $status, array $payload = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->status = $status;
        $this->payload = $payload;
    }
}
