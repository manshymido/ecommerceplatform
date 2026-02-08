<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Custom validation exception for domain validation errors.
 */
class ValidationException extends DomainException
{
    /**
     * Field-specific validation errors.
     *
     * @var array<string, array<string>>
     */
    protected array $errors = [];

    /**
     * @param array<string, array<string>> $errors
     */
    public function __construct(
        string $message = 'The given data was invalid.',
        array $errors = [],
        ?string $errorCode = 'VALIDATION_ERROR'
    ) {
        parent::__construct(
            message: $message,
            errorCode: $errorCode,
            statusCode: 422
        );

        $this->errors = $errors;
    }

    /**
     * Get the validation errors.
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Create from a single field error.
     */
    public static function forField(string $field, string $message): self
    {
        return new self(
            message: $message,
            errors: [$field => [$message]]
        );
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error_code' => $this->getErrorCode(),
            'errors' => $this->errors,
        ], $this->getStatusCode());
    }
}
