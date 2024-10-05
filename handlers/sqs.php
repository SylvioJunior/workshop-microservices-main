<?php

declare(strict_types=1);

/**
 * SQS Handler for processing AWS SQS messages
 *
 * This file defines constants, sets up autoloading, and implements
 * an SQS handler to process incoming messages.
 */

// Define constants
define('DS', DIRECTORY_SEPARATOR);
define('ROOTPATH', '/var/task/');
define('COREPATH', ROOTPATH . 'core' . DS);
define('APPPATH', ROOTPATH . 'app' . DS);
define('TMPPATH', DS . 'tmp' . DS);

// Set up autoloading and bootstrap
require_once ROOTPATH . 'vendor' . DS . 'autoload.php';
require_once COREPATH . 'bootstrap.php';
require_once APPPATH . 'routes.php';

use Bref\Context\Context;
use Bref\Event\Sqs\SqsEvent;
use Bref\Event\Sqs\SqsHandler;
use core\base\Request;
use core\base\Router;
use core\exceptions\AppException;

/**
 * Handler class for processing SQS messages
 */
class Handler extends SqsHandler
{
    /**
     * Handle incoming SQS messages
     *
     * @param SqsEvent $event The SQS event containing messages
     * @param Context $context The Lambda context
     */
    public function handleSqs(SqsEvent $event, Context $context): void
    {
        foreach ($event->getRecords() as $record) {
            $message = json_decode($record->getBody(), true);
            $this->processMessage($message, $record);
        }
    }

    /**
     * Process a single SQS message
     *
     * @param array $message The decoded message body
     * @param object $record The SQS record object
     */
    private function processMessage(array $message, object $record): void
    {
        try {
            $this->loadRequest($message, $record);
            $result = $this->dispatchRoute($message);
            echo $this->formatResult($result);
        } catch (AppException $e) {
            echo json_encode($e->getDetails());
        } catch (\Throwable $throwable) {
            $this->handleError($message, $throwable);
        }
    }

    /**
     * Load the request with SQS message data
     *
     * @param array $message The decoded message body
     * @param object $record The SQS record object
     */
    private function loadRequest(array $message, object $record): void
    {
        Request::load(
            'cmd',
            'aws-sqs',
            '',
            'cmd',
            $message,
            $message,
            array_merge($message, [
                'sqs-message-id' => $record->getMessageId(),
                'sqs-receipt-handle' => $record->getReceiptHandle(),
                'process-uuid' => $message['process-uuid'] ?? null
            ])
        );
    }

    /**
     * Dispatch the route based on the message path
     *
     * @param array $message The decoded message body
     * @return mixed The result of the route dispatch
     */
    private function dispatchRoute(array $message)
    {
        return Router::dispatch('CMD', $message['path'] ?? '');
    }

    /**
     * Format the result for output
     *
     * @param mixed $result The result to format
     * @return string The formatted result
     */
    private function formatResult($result): string
    {
        return is_array($result) || is_object($result) ? json_encode($result) : (string)$result;
    }

    /**
     * Handle and log errors
     *
     * @param array $message The decoded message body
     * @param \Throwable $throwable The caught exception
     */
    private function handleError(array $message, \Throwable $throwable): void
    {

        echo json_encode([
            'status' => $throwable->getCode() ?: 500,
            'data' => $throwable->getMessage()
        ]);
    }
}

return new Handler();
