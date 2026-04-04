<?php

declare(strict_types=1);

namespace App\Services\FloodApiExceptions;

final class ApiValidationException extends \RuntimeException
{
    /** @var array<string, array<int, string>> */
    public array $errors;

    /**
     * @param array<string, array<int, string>> $errors
     */
    public function __construct(string $message, array $errors, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->errors = $errors;
    }
}
