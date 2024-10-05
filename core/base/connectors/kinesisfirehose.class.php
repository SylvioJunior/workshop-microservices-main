<?php

declare(strict_types=1);

/**
 * Controller class for accessing Kinesis Firehose functions
 *
 * PHP Version 8.1
 *
 * @category Kinesis Firehose
 * @package  Core\Base\Connectors
 * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license  Proprietary
 * @link     http://no.tld
 */

namespace Core\Base\Connectors;

use App\Utils\Parameters\ParameterService;
use Core\Base\AppUsage;

/**
 * KinesisFirehose Class
 *
 * Manages connections and operations for AWS Kinesis Firehose
 *
 * @category Kinesis Firehose
 * @package  Core\Base\Connectors
 * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license  Proprietary
 * @link     http://no.tld
 */
class KinesisFirehose extends AppUsage
{
    /**
     * @var mixed Kinesis Firehose client connection
     */
    private $connection;

    /**
     * @var string Identifier for the connection
     */
    private string $tag;

    /**
     * @var array<string, mixed> Connection parameters
     */
    private array $params;

    /**
     * KinesisFirehose constructor.
     *
     * @param string               $tag    Connection identifier
     * @param array<string, mixed> $params Connection parameters
     */
    public function __construct(string $tag, array $params = [])
    {
        $this->tag = $tag;
        $this->params = $params;
        $this->open();
    }

    /**
     * Opens connection to Kinesis Firehose
     *
     * @return void
     */
    public function open(): void
    {
        if (empty($this->connection)) {
            // Initialize the connection if it does not exist
            $this->connection = ParameterService::load($this->tag, $this->params);
        }
    }

    /**
     * Put a record into Kinesis Firehose
     *
     * @param string $streamName Name of the delivery stream
     * @param mixed  $recordData Data to be sent
     *
     * @return mixed Result from putRecord operation
     */
    public function put(string $streamName, mixed $recordData): mixed
    {
        $this->open();

        $result = $this->connection->putRecord([
            'DeliveryStreamName' => $streamName,
            'Record' => [
                'Data' => json_encode($recordData, JSON_THROW_ON_ERROR)
            ]
        ]);

        return $result;
    }
}
