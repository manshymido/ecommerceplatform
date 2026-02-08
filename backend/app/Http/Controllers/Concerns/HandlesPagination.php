<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Trait for handling pagination in controllers.
 */
trait HandlesPagination
{
    /**
     * Default items per page.
     */
    protected int $defaultPerPage = 15;

    /**
     * Maximum items per page.
     */
    protected int $maxPerPage = 100;

    /**
     * Get the number of items per page from request.
     *
     * @param Request $request
     * @return int
     */
    protected function getPerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', $this->defaultPerPage);

        return min(max($perPage, 1), $this->maxPerPage);
    }

    /**
     * Get pagination parameters from request.
     *
     * @param Request $request
     * @return array{per_page: int, page: int}
     */
    protected function getPaginationParams(Request $request): array
    {
        return [
            'per_page' => $this->getPerPage($request),
            'page' => max((int) $request->input('page', 1), 1),
        ];
    }

    /**
     * Get sorting parameters from request.
     *
     * @param Request $request
     * @param string $defaultSort
     * @param string $defaultDirection
     * @param array<string> $allowedSorts
     * @return array{sort: string, direction: string}
     */
    protected function getSortParams(
        Request $request,
        string $defaultSort = 'created_at',
        string $defaultDirection = 'desc',
        array $allowedSorts = []
    ): array {
        $sort = $request->input('sort', $defaultSort);
        $direction = strtolower($request->input('direction', $defaultDirection));

        // Validate sort field if allowed sorts specified
        if (! empty($allowedSorts) && ! in_array($sort, $allowedSorts, true)) {
            $sort = $defaultSort;
        }

        // Validate direction
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $defaultDirection;
        }

        return [
            'sort' => $sort,
            'direction' => $direction,
        ];
    }
}
