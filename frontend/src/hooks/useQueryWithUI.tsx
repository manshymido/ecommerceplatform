import type { ReactNode } from 'react';
import { useQuery, type UseQueryOptions, type UseQueryResult } from '@tanstack/react-query';
import { ErrorMessage } from '../components/ErrorMessage';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { getApiErrorMessage } from '../utils/apiError';

export type UseQueryWithUIOptions<TData = unknown> = UseQueryOptions<TData> & {
  fallbackMessage: string;
};

/**
 * Wraps useQuery and provides a render() that returns error or loading UI.
 * Usage: const { data, render, ...rest } = useQueryWithUI({ queryKey, queryFn, fallbackMessage });
 *        const ui = render(); if (ui) return ui;
 *        // then use data
 */
export function useQueryWithUI<TData = unknown>(
  options: UseQueryWithUIOptions<TData>
): UseQueryResult<TData> & { render: () => ReactNode } {
  const { fallbackMessage, ...queryOptions } = options;
  const result = useQuery(queryOptions);
  const { error, isLoading, refetch } = result;

  const render = (): ReactNode => {
    if (error) {
      return (
        <ErrorMessage
          message={getApiErrorMessage(error, fallbackMessage)}
          onRetry={() => refetch()}
        />
      );
    }
    if (isLoading) return <LoadingSpinner />;
    return null;
  };

  return { ...result, render };
}
