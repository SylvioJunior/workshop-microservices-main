<?php

declare(strict_types=1);

namespace App\Utils;

use Core\Base\Router;

use App\Utils\Cache\CacheController;


/**
 * Class UtilsRoutes
 *
 * This abstract class defines utility routes for the application.
 */
abstract class UtilsRoutes
{
    /**
     * Register all utility routes.
     *
     * @return void
     */
    public static function register(): void
    {
        self::registerCacheRoutes();
    }

    /**
     * Register cache routes.
     *
     * @return void
     */
    private static function registerCacheRoutes(): void
    {
        Router::get('utils/cache', [CacheController::class, 'get']);
        Router::post('utils/cache', [CacheController::class, 'set']);
    }
}
