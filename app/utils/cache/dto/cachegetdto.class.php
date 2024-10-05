<?php

declare(strict_types=1);

namespace App\Utils\Cache\Dto;

use Core\Base\Dto;
use Validation;

/**
 * Class CacheGetDto
 *
 * Data Transfer Object for cache retrieval operations.
 */
class CacheGetDto extends Dto
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
}
