<?php

declare(strict_types=1);

/**
 * Application Initialization
 *
 * This file is responsible for initializing the application, including autoloading
 * and environment variable loading.
 *
 * @category Core
 * @package  Core\Base
 * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license  Proprietary
 */

// Project autoloader
require_once ROOTPATH . 'core' . DS . 'base' . DS . 'autoloader.class.php';

// Composer autoloader - Local
$localComposerAutoloader = '/home/site/vendor/autoload.php';
if (is_file($localComposerAutoloader)) {
    require_once $localComposerAutoloader;
}

// Composer autoloader - Lambda Layer
$lambdaLayerComposerAutoloader = '/opt/vendor/autoload.php';
if (is_file($lambdaLayerComposerAutoloader)) {
    require_once $lambdaLayerComposerAutoloader;
}

// Register project autoloader
\core\base\Autoloader::register();

// Load environment variables
try {
    $dotenv = new Symfony\Component\Dotenv\Dotenv();
    $dotenv->load(ROOTPATH . '.env');
} catch (\Throwable $t) {
    // Silently handle any issues with loading environment variables
}
