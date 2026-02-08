<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Base exception for domain-specific errors.
 *
 * Use this for business logic violations that should return a 422 response.
 */
class DomainException extends Exception
{
    /**
     * HTTP status code for the response.
     */
    protected int $statusCode = 422;

    /**
     * Additional error context.
     *
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Error code for client identification.
     */
    protected ?string $errorCode = null;

    public function __construct(
        string $message = 'A domain error occurred',
        ?string $errorCode = null,
        array $context = [],
        int $statusCode = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->errorCode = $errorCode;
        $this->context = $context;
        $this->statusCode = $statusCode;
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Get the error context.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        $response = [
            'message' => $this->getMessage(),
        ];

        if ($this->errorCode !== null) {
            $response['error_code'] = $this->errorCode;
        }

        if (! empty($this->context) && config('app.debug')) {
            $response['context'] = $this->context;
        }

        return response()->json($response, $this->statusCode);
    }
}
