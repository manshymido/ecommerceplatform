<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Http\ApiResponse;
use App\Http\Controllers\Concerns\HandlesPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\AbstractPaginator;
use Throwable;

/**
 * Base controller for API and Admin controllers.
 *
 * Centralizes ApiResponse so child controllers do not need to
 * repeat "use App\Http\ApiResponse".
 *
 * Provides common patterns for pagination, validation, and response handling.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class ApiBaseController extends Controller
{
    use HandlesPagination;

    /**
     * Return a single resource response.
     */
    protected function data(JsonResource $resource, int $status = 200): JsonResponse
    {
        return ApiResponse::data($resource, $status);
    }

    /**
     * Return a created resource response (201).
     */
    protected function created(JsonResource $resource): JsonResponse
    {
        return ApiResponse::data($resource, 201);
    }

    /**
     * Return a collection response.
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection|iterable $collection
     */
    protected function collection($collection): JsonResponse
    {
        return ApiResponse::collection($collection);
    }

    /**
     * Return a paginated response.
     */
    protected function paginated(AbstractPaginator $paginator, $resourceCollection): JsonResponse
    {
        return ApiResponse::paginated($paginator, $resourceCollection);
    }

    /**
     * Return a 404 not found response.
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return ApiResponse::notFound($message);
    }

    /**
     * Return a 422 unprocessable entity response.
     */
    protected function unprocessable(string $message, int $status = 422): JsonResponse
    {
        return ApiResponse::unprocessable($message, $status);
    }

    /**
     * Return a 403 forbidden response.
     */
    protected function forbidden(string $message = 'This action is unauthorized.'): JsonResponse
    {
        return ApiResponse::forbidden($message);
    }

    /**
     * Return a 401 unauthorized response.
     */
    protected function unauthorized(string $message = 'Unauthenticated.'): JsonResponse
    {
        return ApiResponse::unauthorized($message);
    }

    /**
     * Handle a domain exception and return appropriate response.
     */
    protected function fromDomainException(Throwable $e): JsonResponse
    {
        if ($e instanceof DomainException) {
            return $e->render();
        }

        return ApiResponse::fromDomainException($e);
    }

    /**
     * Return a successful deletion response.
     */
    protected function deleted(string $entityName = 'Resource'): JsonResponse
    {
        return ApiResponse::deleted($entityName);
    }

    /**
     * Return a generic success response.
     */
    protected function success(array $data, int $status = 200): JsonResponse
    {
        return ApiResponse::success($data, $status);
    }

    /**
     * Return a 204 no content response.
     */
    protected function noContent(): JsonResponse
    {
        return ApiResponse::noContent();
    }

    /**
     * Execute an action and handle any domain exceptions.
     *
     * @template T
     * @param callable(): T $action
     * @param callable(T): JsonResponse $onSuccess
     */
    protected function tryAction(callable $action, callable $onSuccess): JsonResponse
    {
        try {
            $result = $action();
            return $onSuccess($result);
        } catch (DomainException $e) {
            return $e->render();
        } catch (\DomainException $e) {
            return $this->fromDomainException($e);
        }
    }

}
