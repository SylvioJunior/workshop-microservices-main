<?php

declare(strict_types=1);

namespace App\Utils\Cache\Dto;

use Core\Base\Dto;

/**
 * Class CacheSetDto
 *
 * Data Transfer Object for cache set operations.
 */
class CacheSetDto extends Dto
{
    /**
     * The cache key.
     *
     * @var string
     *
     * @Validation\string(msg="Chave de cache deve ser uma string.")
     * @Validation\notEmpty(msg="Chave de cache não especificada.")
     */
    public ?string $key;

    /**
     * The value to be cached.
     *
     * @var mixed
     * 
     */
    public mixed $value;

    /**
     * Time to live in seconds.
     *
     * @var int
     *
     * @Validation\integer(msg="TTL do cache deve ser um número em segundos.")
     * @CustomSanitization\defaultExpires()
     */
    public ?int $ttl;

    /**
     * Set default expiration time if not provided.
     *
     * @param mixed $val The input value
     * @return int The TTL value in seconds
     */
    public function defaultExpires(mixed $val): int
    {
        if ($val === "") {
            return 3600; // 60 * 60 seconds (1 hour)
        }

        return (int) $val;
    }
}
