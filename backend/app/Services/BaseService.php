<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Abstract base service with common functionality.
 *
 * Provides caching, transactions, and logging helpers for application services.
 */
abstract class BaseService
{
    /**
     * Default cache TTL in seconds (1 hour).
     */
    protected int $cacheTtl = 3600;

    /**
     * Cache key prefix.
     */
    protected string $cachePrefix = '';

    /**
     * Get the cache key prefix for this service.
     */
    public function getCachePrefix(): string
    {
        if ($this->cachePrefix === '') {
            return strtolower(class_basename($this));
        }

        return $this->cachePrefix;
    }

    /**
     * Get the default cache TTL in seconds.
     */
    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    /**
     * Clear all cached data for this service using cache tags.
     */
    public function clearCache(): void
    {
        Cache::tags([$this->getCachePrefix()])->flush();
    }

    /**
     * Forget a specific cache key.
     */
    public function forgetCache(string $key): void
    {
        Cache::forget($this->buildCacheKey($key));
    }

    /**
     * Build a cache key with prefix.
     *
     * @param string $key
     * @return string
     */
    protected function buildCacheKey(string $key): string
    {
        return $this->getCachePrefix() . ':' . $key;
    }

    /**
     * Get from cache or execute callback.
     *
     * @template T
     *
     * @param string $key
     * @param callable(): T $callback
     * @param int|null $ttl
     * @return T
     */
    protected function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return Cache::remember(
            $this->buildCacheKey($key),
            $ttl ?? $this->getCacheTtl(),
            $callback
        );
    }

    /**
     * Execute callback in a database transaction.
     *
     * @template T
     *
     * @param callable(): T $callback
     * @param int $attempts
     * @return T
     *
     * @throws Throwable
     */
    protected function transaction(callable $callback, int $attempts = 1): mixed
    {
        return DB::transaction($callback, $attempts);
    }

    /**
     * Log an info message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info("[{$this->getCachePrefix()}] {$message}", $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning("[{$this->getCachePrefix()}] {$message}", $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error("[{$this->getCachePrefix()}] {$message}", $context);
    }

    /**
     * Execute an action and handle exceptions gracefully.
     *
     * @template T
     *
     * @param callable(): T $action
     * @param T $default
     * @param bool $logException
     * @return T
     */
    protected function safely(callable $action, mixed $default = null, bool $logException = true): mixed
    {
        try {
            return $action();
        } catch (Throwable $e) {
            if ($logException) {
                $this->logError('Action failed: ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            return $default;
        }
    }
}
