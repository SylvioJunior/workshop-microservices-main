<?php

declare(strict_types=1);

namespace core\base\connectors {

    use app\utils\parameters\ParameterService;

    /**
     * AWS Athena Controller Class
     * Connector for AWS/Athena
     *
     * @category Athena
     * @package  core\base
     */
    class AthenaLogs
    {
        private $connection;
        private string $tag;

        /**
         * AthenaLogs constructor.
         *
         * @param string $tag Identifier for the connection
         */
        public function __construct(string $tag)
        {
            $this->tag = $tag;
            $this->open();
        }

        /**
         * Opens the connection.
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
         * Executes a query on the specified database.
         *
         * @param string $database     The name of the database to use
         * @param string $queryString  The SQL query to execute
         * @param string $outputS3Bucket The S3 bucket to save the results
         *
         * @return string[] The location of the query results
         */
        public function query(string $database, string $queryString, string $outputS3Bucket): array
        {
            $this->open();

            $result = $this->connection->startQueryExecution([
                'QueryExecutionContext' => [
                    'Database' => $database,
                ],
                'QueryString' => $queryString,
                'ResultConfiguration' => [
                    'OutputLocation' => $outputS3Bucket,
                ],
            ]);

            $queryExecutionId = $result->get('QueryExecutionId');
            $backoffTime = 200000;
            $resultLocation = [];

            do {
                usleep($backoffTime);

                $result = $this->connection->getQueryExecution([
                    'QueryExecutionId' => $queryExecutionId,
                ]);

                $status = $result->get('QueryExecution')['Status']['State'];

                if ($status === 'SUCCEEDED') {
                    // Query succeeded, get the results location
                    $resultLocation = $result->get('QueryExecution')['ResultConfiguration']['OutputLocation'];
                } elseif ($status === 'FAILED' || $status === 'CANCELLED') {
                    // Handle query failure or cancellation as necessary
                    break;
                } else {
                    $backoffTime = min($backoffTime * 2, 29000000); // Limit wait time to 29 seconds in microseconds
                }
            } while ($status === 'FAILED' || $status === 'CANCELLED' || $status !== 'SUCCEEDED');

            return $resultLocation;
        }
    }
}
