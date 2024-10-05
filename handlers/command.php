<?php

declare(strict_types=1);

/**
 * Command Handler
 *
 * This script handles command-line requests, processes them through the application's routing system,
 * and logs the results.
 */

// Define constants
define('DS', DIRECTORY_SEPARATOR);
define('ROOTPATH', '/var/task/');
define('COREPATH', ROOTPATH . 'core' . DS);
define('APPPATH', ROOTPATH . 'app' . DS);
define('TMPPATH', DS . 'tmp' . DS);

// Load dependencies
require_once ROOTPATH . 'vendor' . DS . 'autoload.php';
require_once COREPATH . 'bootstrap.php';
require_once APPPATH . 'routes.php';

use core\base\Request;
use core\base\Router;

use core\exceptions\AppException;

try {
    $payload = json_decode(base64_decode($argv[2] ?? ''), true) ?? [];
    $metaData = $payload['metaData'] ?? [];

    if (isset($payload['processUuid'])) {
        $metaData['process-uuid'] = $payload['processUuid'];
    }

    Request::load(
        'cmd',
        'php-console',
        '',
        'cmd',
        $payload['parameters'] ?? [],
        $payload['parameters'] ?? [],
        $metaData
    );

    // Route the request to its corresponding controller
    $result = Router::dispatch('CMD', $argv[1] ?? '');

    echo is_array($result ?? null) || is_object($result ?? null) ? json_encode($result ?? null) : $result;
} catch (AppException $e) {
    echo json_encode($e->getDetails());
} catch (\Throwable $throwable) {


    echo json_encode([
        'status' => $throwable->errorCode ?? 500,
        'data' => $throwable->getMessage()
    ]);
}
