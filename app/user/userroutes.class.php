<?php

declare(strict_types=1);

namespace App\User;

use Core\Base\Router;
use App\User\UserController;

/**
 * Class UserRoutes
 *
 * Defines user-related routes.
 */
class UserRoutes
{
    /**
     * Registers user routes.
     */
    public static function register(): void
    {
        // Route to list users
        Router::get(
            'users/list',
            [UserController::class, 'list']
        );

        // Route to get a specific user
        Router::get(
            'users',
            [UserController::class, 'get']
        );

        // Route to create a new user
        Router::post(
            'users',
            [UserController::class, 'create']
        );

        // Route to update an existing user
        Router::put(
            'users',
            [UserController::class, 'update']
        );

        // Route to delete a user
        Router::delete(
            'users',
            [UserController::class, 'delete']
        );
    }
}
