<?php

define("DS", DIRECTORY_SEPARATOR);
define("ROOTPATH", dirname(dirname(__FILE__)) . DS);
define("COREPATH", ROOTPATH . 'core' . DS);
define("APPPATH", ROOTPATH . 'app' . DS);
define("TMPPATH", ROOTPATH  . 'tmp' . DS);

require_once ROOTPATH . 'vendor' . DS . 'autoload.php';
require_once COREPATH . 'bootstrap.php';
require_once APPPATH . 'routes.php';

try {

    $payload = json_decode(base64_decode(@$argv[2] ?? ''), true);

    \core\base\Request::load(
        'cmd',
        'php-console',
        '',
        'cmd',
        $payload['parameters'] ?? [],
        $payload['parameters'] ?? [],
        [],
    );

    // Encaminhando a rota para seu devido controlador
    $return = \core\base\Router::dispatch(
        'CMD',
        @$argv[1]
    );

    $result = is_array($return ?? null) ||
        is_object($return ?? null) ?
        json_encode($return ?? null) :
        $return;

    echo $result;
} catch (\core\exceptions\AppException $e) {

    $result = json_encode($e->getDetails());

    echo $result;
} catch (\Throwable $e) {

    $result = json_encode(
        [
            'status' => $e->errorCode ?? 500,
            'data' => $e->getMessage()
        ]
    );

    echo $result;
}
