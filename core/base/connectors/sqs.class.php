<?php

declare(strict_types=1);

/**
 * SQS Queue Connector
 *
 * PHP Version 8.1
 *
 * @category Queue
 * @package  Core\Base\Connectors
 * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license  Proprietary
 * @link     http://no.tld
 */

namespace Core\Base\Connectors;

use Core\Base\AppUsage;

use App\Utils\Parameters\ParameterService;

/**
 * SQS Class
 *
 * Manages connections and operations for AWS SQS (Simple Queue Service)
 *
 * @category Queue
 * @package  Core\Base\Connectors
 * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license  Proprietary
 * @link     http://no.tld
 */
class SQS extends AppUsage
{
    /**
     * @var mixed SQS client connection
     */
    private $connection;

    /**
     * @var string Identifier for the connection
     */
    private ?string $tag;

    /**
     * SQS constructor.
     *
     * @param string $tag Identifier for the connection
     */
    public function __construct(string $tag)
    {
        $this->tag = $tag;
        $this->open();
    }

    /**
     * Opens connection to the SQS service
     *
     * @return void
     */
    public function open(): void
    {
        if (empty($this->connection)) {
            // Initialize the connection if it does not exist
            $this->connection = ParameterService::load($this->tag);
        }
    }

    /**
     * Receive messages from the queue
     *
     * @param string $tag    Connection identifier
     * @param string $queueUrl Queue URL
     * @param int    $wait   Wait time in seconds
     * @param int    $maxNum Maximum number of messages to receive
     *
     * @return array|null
     */
    public function receive(string $tag, string $queueUrl, int $wait = 20, int $maxNum = 1): ?array
    {
        $this->open();

        $result = $this->connection[$tag]->receiveMessage([
            'QueueUrl'            => $queueUrl,
            'WaitTimeSeconds'     => $wait,
            'MaxNumberOfMessages' => $maxNum
        ]);

        return $result->get('Messages');
    }

    /**
     * Delete a message from the queue
     *
     * @param string $receiptHandle Receipt handle of the message
     * @param string $queueUrl      Queue URL
     *
     * @return mixed
     */
    public function delete(string $receiptHandle, string $queueUrl)
    {
        $this->open();

        return $this->connection->deleteMessage([
            'QueueUrl'      => $queueUrl,
            'ReceiptHandle' => $receiptHandle
        ]);
    }

    /**
     * Send a message to the queue
     *
     * @param array  $payload Payload to send
     * @param string $groupId Message group ID
     * @param string $queueUrl Queue URL
     *
     * @return mixed
     */
    public function send(array $payload, string $groupId, string $queueUrl)
    {
        $this->open();

        return $this->connection->sendMessage([
            'MessageGroupId' => $groupId,
            'MessageBody'    => json_encode($payload),
            'QueueUrl'       => $queueUrl
        ]);
    }
}
