<?php

declare(strict_types=1);

namespace App\Http;

use App\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Throwable;

/**
 * Centralized API response builder following consistent response structure.
 *
 * All API responses follow the pattern:
 * - Success: { "data": ... } or { "data": ..., "meta": ... }
 * - Error: { "message": "..." } with optional error_code and context
 *
 * @psalm-immutable
 */
final class ApiResponse
{
    /**
     * Return a generic success response with custom data structure.
     */
    public static function success(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    /**
     * Return a single resource wrapped in data key.
     */
    public static function data(JsonResource $resource, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $resource], $status);
    }

    /**
     * Return a created resource response (201).
     */
    public static function created(JsonResource $resource): JsonResponse
    {
        return self::data($resource, 201);
    }

    /**
     * Return a collection of resources wrapped in data key.
     *
     * @param ResourceCollection|iterable $resourceCollection
     */
    public static function collection($resourceCollection): JsonResponse
    {
        return response()->json(['data' => $resourceCollection]);
    }

    /**
     * Return a paginated response with data and meta information.
     *
     * @param AbstractPaginator $paginator
     * @param ResourceCollection|iterable $resourceCollection
     */
    public static function paginated(AbstractPaginator $paginator, $resourceCollection): JsonResponse
    {
        return response()->json([
            'data' => $resourceCollection,
            'meta' => self::buildPaginationMeta($paginator),
        ]);
    }

    /**
     * Build pagination metadata array.
     */
    private static function buildPaginationMeta(AbstractPaginator $paginator): array
    {
        $meta = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];

        // LengthAwarePaginator has additional info
        if (method_exists($paginator, 'total')) {
            $meta['total'] = $paginator->total();
            $meta['last_page'] = $paginator->lastPage();
        }

        return $meta;
    }

    /**
     * Return a 404 not found response.
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return response()->json(['message' => $message], 404);
    }

    /**
     * Return a successful deletion response.
     */
    public static function deleted(string $entityName = 'Resource'): JsonResponse
    {
        return response()->json([
            'message' => $entityName . ' deleted successfully',
        ]);
    }

    /**
     * Return a 422 unprocessable entity response.
     */
    public static function unprocessable(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    /**
     * Return a 400 bad request response.
     */
    public static function badRequest(string $message = 'Bad request'): JsonResponse
    {
        return response()->json(['message' => $message], 400);
    }

    /**
     * Return a response from any throwable exception.
     * Handles our custom DomainException specially.
     */
    public static function fromDomainException(Throwable $e): JsonResponse
    {
        if ($e instanceof DomainException) {
            return $e->render();
        }

        return self::unprocessable($e->getMessage());
    }

    /**
     * Return a 401 unauthorized response.
     */
    public static function unauthorized(string $message = 'Unauthenticated.'): JsonResponse
    {
        return response()->json(['message' => $message], 401);
    }

    /**
     * Return a 403 forbidden response.
     */
    public static function forbidden(string $message = 'This action is unauthorized.'): JsonResponse
    {
        return response()->json(['message' => $message], 403);
    }

    /**
     * Return a 500 server error response.
     */
    public static function serverError(string $message = 'Server Error'): JsonResponse
    {
        return response()->json(['message' => $message], 500);
    }

    /**
     * Return a 204 no content response.
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a validation error response with field-specific errors.
     *
     * @param array<string, array<string>> $errors
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Return a rate limit exceeded response.
     */
    public static function tooManyRequests(string $message = 'Too many requests', int $retryAfter = 60): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'retry_after' => $retryAfter,
        ], 429);
    }

    /**
     * Private constructor to prevent instantiation.
     */
    private function __construct()
    {
        // Static class - prevent instantiation
    }
}
