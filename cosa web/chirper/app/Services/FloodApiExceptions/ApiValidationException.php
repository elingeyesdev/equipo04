<?php

declare(strict_types=1);

namespace App\Services\FloodApiExceptions;

final class ApiValidationException extends ApiRequestException
{
    /** @var array<string, array<int, string>> */
    public array $errors;

    /**
     * @param array<string, array<int, string>> $errors
     */
    public function __construct(string $message, array $errors, int $status = 422, array $payload = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $status, $payload, $code, $previous);

        $this->errors = $errors;
    }
}
