<?php

declare(strict_types=1);

/**
 * API Gateway Handler
 *
 * This file is responsible for processing API Gateway events and routing requests.
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
 * Main function to process API Gateway events
 *
 * @param array $event The event received from API Gateway
 * @param Context $context The execution context
 * @return array The processed response
 */
return function (array $event, Context $context): array {
    try {
        $parsedQuery = parseQueryString($event['queryStringParameters'] ?? []);

        Request::load(
            'http',
            $event['headers']['user-agent'] ?? null,
            $event['requestContext']['http']['sourceIp'] ?? null,
            $event['requestContext']['http']['method'] ?? null,
            $parsedQuery,
            json_decode($event['body'] ?? '', true),
            $event['headers'] ?? []
        );

        $return = Router::dispatch(
            $event['requestContext']['http']['method'] ?? '',
            ltrim($event['rawPath'] ?? '', '/')
        );
    } catch (AppException $e) {
        return $e->getDetails();
    } catch (\Throwable $throwable) {
        return [
            'status' => $throwable->errorCode ?? 500,
            'data' => $throwable->getMessage()
        ];
    }

    return $return;
};

/**
 * Parse query string parameters
 *
 * @param array $queryStringParameters The query string parameters
 * @return array The parsed parameters
 */
function parseQueryString(array $queryStringParameters): array
{
    $parsedQuery = [];
    foreach ($queryStringParameters as $key => $value) {
        parse_str($key . '=' . $value, $tempArray);
        $parsedQuery = array_merge_recursive($parsedQuery, $tempArray);
    }
    return $parsedQuery;
}
