<?php

declare(strict_types=1);

/**
 * Redis Connector
 *
 * This class provides a connection to Redis and various operations.
 *
 * PHP Version 7.4
 *
 * @category Redis
 * @package  Core\Base\Connectors
 * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license  Proprietary
 * @link     http://example.com
 */

namespace Core\Base\Connectors;

use App\Utils\Parameters\ParameterService;
use Core\Base\AppUsage;

/**
 * RedisConnector Class
 *
 * Manages Redis connections and operations.
 *
 * @category Redis
 * @package  Core\Base\Connectors
 * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license  Proprietary
 * @link     http://example.com
 */
class RedisConnector extends AppUsage
{
    /**
     * @var mixed Redis connection
     */
    private $connection;

    /**
     * @var string|null Connection identifier
     */
    private $tag;

    /**
     * @var array Connection parameters
     */
    private $params;

    /**
     * Constructor
     *
     * @param string|null $tag    Connection identifier
     * @param array       $params Connection parameters
     */
    public function __construct(?string $tag = null, array $params = [])
    {
        if ($tag !== null) {
            $this->tag = $tag;
            $this->params = $params;
            $this->open();
        }
    }

    /**
     * Get the Redis connection
     *
     * @return mixed
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set the Redis connection
     *
     * @param mixed $connection Redis connection
     * @return void
     */
    public function setConnection($connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Open connection to Redis server
     *
     * @return void
     * @throws \Throwable
     */
    public function open(): void
    {
        if (empty($this->connection)) {
            $connected = false;

            do {
                try {
                    $this->connection = ParameterService::load($this->tag, $this->params);
                    $connected = true;
                } catch (\Throwable $e) {
                    if (
                        strpos($e->getMessage(), "php_network_getaddresses: getaddrinfo for") !== false ||
                        strpos($e->getMessage(), "Name or service not known") !== false
                    ) {
                        $connected = false;
                        usleep(500000); // Sleep for 0.5 seconds
                    } else {
                        throw $e;
                    }
                }
            } while (!$connected);
        }
    }

    /**
     * Get keys matching a pattern
     *
     * @param string $pattern Pattern to match keys
     * @return array
     */
    public function keys(string $pattern): array
    {
        $this->open();
        return $this->connection->keys($pattern);
    }

    /**
     * Get value of a key
     *
     * @param string $key Key to retrieve
     * @return mixed
     */
    public function get(string $key): mixed
    {
        $this->open();
        $return = $this->connection->get($key);

        return !is_object($return) ? $return : false;
    }

    /**
     * Get multiple values
     *
     * @param array $list List of keys to retrieve
     * @return mixed
     */
    public function mget(array $list): mixed
    {
        $this->open();
        $return = $this->connection->mget($list);

        return !is_object($return) ? $return : [];
    }

    /**
     * Set a key-value pair
     *
     * @param string $key   Key to set
     * @param mixed  $value Value to set
     * @param int    $ttl   Time to live in seconds (optional)
     * @return mixed
     */
    public function set(string $key, $value, int $ttl = 0): mixed
    {
        $this->open();

        if ($ttl > 0) {
            $this->connection->setex($key, $ttl, $value);
        } else {
            $this->connection->set($key, $value);
        }

        return true;
    }

    /**
     * Delete a key
     *
     * @param string $key Key to delete
     * @return int
     */
    public function del(string $key): mixed
    {
        $this->open();
        return $this->connection->del($key);
    }

    /**
     * Increment a key
     *
     * @param string $key Key to increment
     * @return mixed
     */
    public function incr(string $key): mixed
    {
        $this->open();
        $return = $this->connection->incr($key);

        return !is_object($return) ? $return : false;
    }

    /**
     * Start a transaction
     *
     * @return mixed
     */
    public function multi()
    {
        $this->open();
        return $this->connection->multi();
    }

    /**
     * Execute a transaction
     *
     * @return array
     */
    public function exec(): array
    {
        $this->open();
        return $this->connection->exec();
    }

    /**
     * Set expiration time for a key
     *
     * @param string $key Key to set expiration
     * @param int    $ttl Time to live in seconds
     * @return bool
     */
    public function expire(string $key, int $ttl): bool
    {
        $this->open();

        $return = $this->connection->expire($key, $ttl);

        return !is_object($return) ? $return : false;
    }

    /**
     * Ping the Redis server
     *
     * @return string
     */
    public function ping(): string
    {
        $this->open();
        return $this->connection->ping('hello');
    }
}
