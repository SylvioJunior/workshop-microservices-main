<?php

declare(strict_types=1);

/**
 * Controller class for S3 access functions
 *
 * PHP Version 8
 *
 * @category S3
 * @package  Core\Base\Connectors
 * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license  http://no.tld Proprietary License
 * @link     http://no.tld
 */

namespace Core\Base\Connectors;

use App\Utils\Parameters\ParameterService;
use Exception;

/**
 * Controller class for S3 access functions
 *
 * @category S3
 * @package  Core\Base\Connectors
 * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license  http://no.tld Proprietary License
 * @link     http://no.tld
 */
class S3 extends \Core\Base\AppUsage
{
    /**
     * S3 connection object
     *
     * @var mixed
     */
    public $connection;

    /**
     * Connection identifier
     *
     * @var string
     */
    public $tag;

    /**
     * Connection parameters
     *
     * @var array
     */
    public $params;

    /**
     * Constructor
     *
     * @param string $tag    Connection identifier
     * @param array  $params Connection parameters
     */
    public function __construct(string $tag, array $params = [])
    {
        $this->tag = $tag;
        $this->params = $params;
        $this->open();
    }

    /**
     * Opens connection to S3 server
     *
     * @return void
     */
    public function open(): void
    {
        if (empty($this->connection)) {
            $this->connection = ParameterService::load($this->tag, $this->params);
        }
    }

    /**
     * List objects in S3 bucket
     *
     * @param array $params List parameters
     *
     * @return mixed
     */
    public function list(array $params)
    {
        $this->open();
        return $this->connection->listObjectsV2($params);
    }

    /**
     * Check if object exists in S3 bucket
     *
     * @param string $bucketName Bucket name
     * @param string $objectKey  Object key
     *
     * @return bool
     */
    public function exists(string $bucketName, string $objectKey): bool
    {
        $this->open();
        return $this->connection->doesObjectExist($bucketName, $objectKey);
    }

    /**
     * Get object from S3 bucket
     *
     * @param array $params Get parameters
     *
     * @return mixed
     */
    public function get(array $params)
    {
        $this->open();
        return $this->connection->getObject($params);
    }

    /**
     * Put object into S3 bucket
     *
     * @param array $params Put parameters
     *
     * @return mixed
     */
    public function put(array $params)
    {
        $this->open();
        return $this->connection->putObject($params);
    }

    /**
     * Get object metadata from S3 bucket
     *
     * @param array $params Head parameters
     *
     * @return mixed
     */
    public function head(array $params)
    {
        $this->open();
        return $this->connection->headObject($params);
    }

    /**
     * Delete object from S3 bucket
     *
     * @param array $params Delete parameters
     *
     * @return mixed
     */
    public function delete(array $params)
    {
        $this->open();
        return $this->connection->deleteObject($params);
    }

    /**
     * Move object within S3 bucket
     *
     * @param array $params Move parameters
     *
     * @return bool
     * @throws Exception
     */
    public function move(array $params): bool
    {
        $this->open();

        $copyParams = $params;
        $copyParams['CopySource'] = $copyParams['Bucket'] . "/" . $copyParams['CopySource'];

        $copyResult = $this->connection->copyObject($copyParams);

        if ($copyResult['@metadata']['statusCode'] !== 200) {
            throw new Exception("Error copying file to destination");
        }

        $deleteParams = [
            'Bucket' => $params['Bucket'],
            'Key'    => $params['CopySource'],
        ];

        $deleteResult = $this->connection->deleteObject($deleteParams);

        if ($deleteResult['@metadata']['statusCode'] !== 204) {
            throw new Exception("Error removing source file");
        }

        return true;
    }

    /**
     * Generate pre-signed URL for S3 object
     *
     * @param string $commandName Command name
     * @param array  $params      Command parameters
     * @param mixed  $expiration  URL expiration
     *
     * @return string
     */
    public function preSignedUrl(string $commandName, array $params, $expiration): string
    {
        $this->open();

        $command = $this->connection->getCommand($commandName, $params);

        $result = $this->connection->createPresignedRequest($command, $expiration);

        return (string) $result->getUri();
    }
}
