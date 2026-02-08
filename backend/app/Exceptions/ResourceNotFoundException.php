<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Exception for when a requested resource is not found.
 */
class ResourceNotFoundException extends DomainException
{
    public function __construct(
        string $message = 'Resource not found',
        ?string $errorCode = null
    ) {
        parent::__construct(
            message: $message,
            errorCode: $errorCode ?? 'RESOURCE_NOT_FOUND',
            statusCode: 404
        );
    }

    /**
     * Create from resource type and optional identifier.
     */
    public static function forResource(string $resourceType, int|string|null $identifier = null): self
    {
        $message = $identifier !== null
            ? "{$resourceType} with ID {$identifier} not found"
            : "{$resourceType} not found";

        return new self($message);
    }
}
