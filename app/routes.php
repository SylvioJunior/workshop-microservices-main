<?php

declare(strict_types=1);

/**
 * Route Configuration
 *
 * This file registers all the routes for the application.
 *
 * PHP Version 8.1
 *
 * @category Application
 * @package  Routes
 * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license  Proprietary http://no.tld
 * @link     http://no.tld
 */

use App\User\UserRoutes;
use App\Utils\UtilsRoutes;


// Utilities
UtilsRoutes::register();
UserRoutes::register();
