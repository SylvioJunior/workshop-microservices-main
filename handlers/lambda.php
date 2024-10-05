<?php

declare(strict_types=1);

/**
 * Lambda function initialization file.
 *
 * This file defines essential constants, loads dependencies,
 * and configures the event handler for the Lambda function.
 */

// Constants definition
define('DS', DIRECTORY_SEPARATOR);
define('ROOTPATH', '/var/task/');
define('COREPATH', ROOTPATH . 'core' . DS);
define('APPPATH', ROOTPATH . 'app' . DS);
define('TMPPATH', DS . 'tmp' . DS);

// Dependencies loading
require_once ROOTPATH . 'vendor' . DS . 'autoload.php';
require_once COREPATH . 'bootstrap.php';
require_once APPPATH . 'routes.php';

use Bref\Context\Context;
use core\base\Request;
use core\base\Router;
use core\exceptions\AppException;


/**
 * Main Lambda function.
 *
 * @param array $event The event received by Lambda
 * @param Context $context The context of Lambda execution
 * @return array The execution result
 */
return function (array $event, Context $context): array {
    try {
        Request::load('cmd', '', '', '', $event, $event, $event);

        // Route the request to its corresponding controller
        $result = Router::dispatch('CMD', $event['path'] ?? '');
    } catch (AppException $e) {
        return $e->getDetails();
    } catch (\Throwable $throwable) {

        $result = [
            'status' => $throwable->errorCode ?? 500,
            'data' => $throwable->getMessage()
        ];
    }

    return $result;
};
