<?php

namespace App\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\AbstractPaginator;

class ApiResponse
{
    public static function success(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    public static function data(JsonResource $resource, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $resource], $status);
    }

    public static function collection($resourceCollection): JsonResponse
    {
        return response()->json(['data' => $resourceCollection]);
    }

    public static function paginated(AbstractPaginator $paginator, $resourceCollection): JsonResponse
    {
        return response()->json([
            'data' => $resourceCollection,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return response()->json(['message' => $message], 404);
    }

    public static function deleted(string $entityName = 'Resource'): JsonResponse
    {
        return response()->json(['message' => $entityName . ' deleted successfully']);
    }

    public static function unprocessable(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    /**
     * Return 422 response from a domain/validation exception (DRY for catch blocks).
     */
    public static function fromDomainException(\Throwable $e): JsonResponse
    {
        return self::unprocessable($e->getMessage());
    }
}
