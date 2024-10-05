<?php

declare(strict_types=1);

namespace core\base\connectors {

    use app\utils\parameters\ParameterService;

    /**
     * CloudwatchLogs Class
     * Handles interactions with AWS CloudWatch Logs
     *
     * @category Cloudwatch Logs
     * @package  core\base
     */
    class CloudwatchLogs
    {
        /**
         * @var mixed AWS CloudWatch Logs client connection
         */
        private $connection;

        /**
         * @var string Identifier for the connection
         */
        private string $tag;

        /**
         * CloudwatchLogs constructor.
         *
         * @param string $tag Identifier for the connection
         */
        public function __construct(string $tag)
        {
            $this->tag = $tag;
            $this->open();
        }

        /**
         * Opens connection to the CloudWatch Logs service
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
         * Logs an event to CloudWatch Logs
         *
         * @param object $dto        Data Transfer Object containing log information
         * @param string $groupName  Name of the log group
         * @param string $streamName Name of the log stream
         *
         * @return mixed Response from CloudWatch Logs putLogEvents API
         */
        public function log(object $dto, string $groupName, string $streamName)
        {
            $this->open();

            return $this->connection->putLogEvents([
                'logGroupName' => $groupName,
                'logStreamName' => $streamName,
                'logEvents' => [
                    [
                        'timestamp' => (int)(strtotime($dto->datetime) * 1000),
                        'message' => json_encode($dto)
                    ],
                ]
            ]);
        }

        /**
         * Executes a query on CloudWatch Logs
         *
         * @param array $data Query parameters
         *
         * @return array Query results
         */
        public function query(array $data): array
        {
            $this->open();

            $result = $this->connection->startQuery($data);
            $queryId = $result['queryId'];
            $backoffTime = 200000;

            do {
                usleep($backoffTime);

                $results = $this->connection->getQueryResults([
                    'queryId' => $queryId,
                ]);

                $status = $results['status'];

                if ($status === 'Running' || $status === 'Scheduled') {
                    // Increase wait time using exponential backoff
                    $backoffTime = min($backoffTime * 2, 29000000); // Limit wait time to 29 seconds in microseconds
                }
            } while ($status === 'Running' || $status === 'Scheduled');

            return $results['results'];
        }
    }
}
