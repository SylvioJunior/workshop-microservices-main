<?php

declare(strict_types=1);

namespace App\Utils\Cache;

use Core\Base\Request;

/**
 * Abstract class for handling cache operations.
 */
abstract class CacheController
{
    /**
     * Set a value in the cache.
     *
     * @return array The response with status and data.
     */
    public static function set(): array
    {
        extract(Request::$payload ?? [], EXTR_SKIP);

        CacheService::setContext(
            Request::$workspace,
            Request::$user
        );

        $return = CacheService::set(
            $key ?? '',
            $value ?? null,
            $ttl ?? false
        );

        return [
            'status' => 200,
            'data' => [
                $return
            ]
        ];
    }

    /**
     * Get a value from the cache.
     *
     * @return array The response with status and data.
     */
    public static function get(): array
    {
        extract(Request::$data ?? [], EXTR_SKIP);

        CacheService::setContext(
            Request::$workspace,
            Request::$user
        );

        $return = CacheService::get(
            $key ?? ''
        );

        return [
            'status' => 200,
            'data' => [
                $return
            ]
        ];
    }
}
