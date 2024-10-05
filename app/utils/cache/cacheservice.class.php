<?php

declare(strict_types=1);

namespace App\Utils\Cache;

use Core\Base\Connection;
use Core\Base\Service;
use Core\Exceptions\ValidationException;

use App\Utils\Cache\Dto\CacheGetDto;
use App\Utils\Cache\Dto\CacheSetDto;

/**
 * CacheService Class
 *
 * Provides methods for interacting with a Redis cache.
 */
abstract class CacheService extends Service
{
    /** @var array Cached keys */
    public static array $keysSaved = [];

    /**
     * Set a value in the cache
     *
     * @param string $key   The cache key
     * @param mixed  $value The value to cache
     * @param int|bool $ttl Time to live in seconds, or false for no expiration
     * @return bool True on success
     */
    public static function set(string $key, mixed $value = null, int $ttl = 60 * 60 * 24): bool
    {
        $data = new CacheSetDto(compact('key', 'value', 'ttl'));

        $conexao = Connection::open('redisconnector', 'env_ip_redis');

        unset(self::$keysSaved[$key]);

        $conexao->set($data->key, $data->value, $data->ttl);

        return true;
    }

    /**
     * Set multiple values in the cache
     *
     * @param array $list Key-value pairs to cache
     * @param int $ttl Time to live in seconds
     * @return array|bool Result of the operation
     * @throws ValidationException If the list is not an array
     */
    public static function mset(array $list, int $ttl = 60 * 60 * 24): array|bool
    {
        if (!is_array($list)) {
            throw new ValidationException(json_encode([
                'list' => 'Este item deve ser uma lista'
            ]));
        }

        $conexao = Connection::open('redisconnector', 'env_ip_redis');

        $conexao->multi();

        foreach ($list as $key => $value) {
            self::set($key, $value, $ttl);
        }

        return $conexao->exec();
    }

    /**
     * Get a value from the cache
     *
     * @param string $key The cache key
     * @param bool $getKeyFromMemory Whether to retrieve from memory first
     * @return mixed The cached value or false if not found
     */
    public static function get(string $key, bool $getKeyFromMemory = false): mixed
    {
        $data = new CacheGetDto(compact('key'));

        if ($getKeyFromMemory && isset(self::$keysSaved[$data->key])) {
            return self::$keysSaved[$data->key];
        }

        $conexao = Connection::open('redisconnector', 'env_ip_redis');
        $cacheReturn = $conexao->get($data->key);

        if ($getKeyFromMemory) {
            self::$keysSaved[$data->key] = $cacheReturn;
        }

        return $cacheReturn;
    }

    /**
     * Get keys matching a pattern
     *
     * @param string $key The pattern to match
     * @return array Matching keys
     */
    public static function keys(string $key): array
    {
        $data = new CacheGetDto(compact('key'));
        $conexao = Connection::open('redisconnector', 'env_ip_redis');

        return $conexao->keys($data->key);
    }

    /**
     * Get multiple values from the cache
     *
     * @param array $list List of keys to retrieve
     * @return array Values of the specified keys
     * @throws ValidationException If the list is not an array
     */
    public static function mget(array $list): array
    {
        if (!is_array($list)) {
            throw new ValidationException(json_encode([
                'list' => 'Este item deve ser uma lista'
            ]));
        }

        $conexao = Connection::open('redisconnector', 'env_ip_redis');

        return $conexao->mget($list) ?? [];
    }

    /**
     * Delete a key from the cache
     *
     * @param string $key The key to delete
     * @return int|bool Number of keys deleted or false on failure
     */
    public static function del(string $key): int|bool
    {
        $data = new CacheGetDto(compact('key'));
        $conexao = Connection::open('redisconnector', 'env_ip_redis');

        unset(self::$keysSaved[$data->key]);

        return $conexao->del($data->key);
    }

    /**
     * Delete multiple keys from the cache
     *
     * @param array $list List of keys to delete
     * @return array|bool Result of the operation
     * @throws ValidationException If the list is not an array
     */
    public static function mdel(array $list): array|bool
    {
        if (!is_array($list)) {
            throw new ValidationException(json_encode([
                'list' => 'Este item deve ser uma lista'
            ]));
        }

        $conexao = Connection::open('redisconnector', 'env_ip_redis');

        $conexao->multi();

        foreach ($list as $item) {

            unset(self::$keysSaved[$item]);

            $conexao->del($item);
        }

        return $conexao->exec();
    }

    /**
     * Increment a key's value
     *
     * @param string $key The key to increment
     * @param int|bool $ttl Time to live in seconds, or false for no expiration
     * @return int The new value
     */
    public static function incr(string $key, int|bool $ttl = false): mixed
    {
        $data = new CacheGetDto(compact('key'));
        $conexao = Connection::open('redisconnector', 'env_ip_redis');

        unset(self::$keysSaved[$key]);

        $returnIncr = $conexao->incr($data->key);

        if ($ttl !== false) {
            $conexao->expire($data->key, $ttl);
        }

        return $returnIncr;
    }

    /**
     * Start a transaction
     *
     * @return mixed Redis connection in multi-exec mode
     */
    public static function multi(): mixed
    {
        $conexao = Connection::open('redisconnector', 'env_ip_redis');
        return $conexao->multi();
    }

    /**
     * Execute a transaction
     *
     * @return array|bool Result of the transaction
     */
    public static function exec(): array|bool
    {
        $conexao = Connection::open('redisconnector', 'env_ip_redis');
        return $conexao->exec();
    }
}
