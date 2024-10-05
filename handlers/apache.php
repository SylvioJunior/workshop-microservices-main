<?php

declare(strict_types=1);

/**
 * Application entry point for HTTP method
 *
 * @category Web
 * @package  Web
 * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license  Proprietary
 */

use core\base\AppUsage;
use core\base\Request;
use core\base\Router;
use core\exceptions\AppException;

// Define constants
define('DS', DIRECTORY_SEPARATOR);
define('ROOTPATH', dirname(__DIR__) . DS);
define('COREPATH', ROOTPATH . 'core' . DS);
define('APPPATH', ROOTPATH . 'app' . DS);
define('TMPPATH', ROOTPATH . 'tmp' . DS);

try {
    // Initialize application
    require_once ROOTPATH . 'vendor' . DS . 'autoload.php';
    require_once COREPATH . 'bootstrap.php';
    require_once APPPATH . 'routes.php';

    // Uncomment to enable usage collection
    // AppUsage::$usageCollect = true;

    // Populate request class
    Request::load(
        'http',
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['REQUEST_METHOD'] ?? '',
        $_REQUEST,
        json_decode(file_get_contents('php://input') ?: '', true) ?? [],
        $_SERVER
    );

    // Route the request to the appropriate controller
    $result = Router::dispatch(
        $_SERVER['REQUEST_METHOD'] ?? '',
        $_GET['url'] ?? ''
    );

    $response = is_array($result ?? null) || is_object($result ?? null) ? json_encode($result ?? null) : (string)$result;

    if (AppUsage::$usageCollect && $response && json_decode($response)) {
        $response = json_encode(
            array_merge(json_decode($response, true) ?? [], [
                '__usageStatistics' => AppUsage::$usageStatistics,
                '__usageHistory' => AppUsage::$usageHistory
            ])
        );
    }

    echo $response;
} catch (AppException $e) {
    $response = json_encode($e->getDetails());

    if (AppUsage::$usageCollect && json_decode($response)) {
        $response = json_encode(
            array_merge(json_decode($response, true), [
                '__usageStatistics' => AppUsage::$usageStatistics,
                '__usageHistory' => AppUsage::$usageHistory
            ])
        );
    }

    echo $response;
} catch (\Throwable $e) {
    echo json_encode([
        'status' => 500,
        'data' => $e->getMessage() . ' - ' . $e->getTraceAsString(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}
