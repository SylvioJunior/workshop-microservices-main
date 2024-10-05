<?php

declare(strict_types=1);

namespace Core\Base\Connectors;

use Core\Base\AppUsage;
use App\Utils\Parameters\ParameterService;

/**
 * Lambda Class
 *
 * Handles interactions with AWS Lambda functions.
 *
 * @category Lambda
 * @package  Core\Base\Connectors
 * @author   André Souza <andrefelipe@ischolar.com.br>
 * @license  http://no.tld Proprietary License
 * @link     http://no.tld
 */
class Lambda extends AppUsage
{
    /**
     * @var mixed AWS Lambda client connection
     */
    private $connection;

    /**
     * @var string Identifier for the connection
     */
    private string $tag;

    /**
     * Lambda constructor.
     *
     * @param string $tag Identifier for the connection
     */
    public function __construct(string $tag)
    {
        $this->tag = $tag;
        $this->open();
    }

    /**
     * Opens connection to the AWS Lambda service
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
     * Invokes a Lambda function
     *
     * @param string $functionName Name of the Lambda function to invoke
     * @param array  $parameters   Parameters to pass to the Lambda function
     *
     * @return mixed Result of the Lambda function invocation
     */
    public function invoke(string $functionName, array $parameters)
    {
        $this->open();

        $result = $this->connection->invoke([
            'FunctionName' => $functionName,
            'InvocationType' => 'RequestResponse',
            'Payload' => json_encode($parameters)
        ]);

        return $result;
    }
}
