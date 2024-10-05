<?php

declare(strict_types=1);

/**
 * DynamoDB Connector
 *
 * This class provides a connection and methods to interact with AWS DynamoDB.
 *
 * PHP Version 8.1
 *
 * @category Database
 * @package  Core\Base\Connectors
 * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license  Proprietary
 * @link     http://no.tld
 */

namespace core\base\connectors {

    use Aws\DynamoDb\Marshaler;
    use app\utils\parameters\ParameterService;
    use core\base\AppUsage;

    /**
     * DynamoDB Class
     *
     * Manages connections and operations for AWS DynamoDB
     *
     * @category Database
     * @package  Core\Base\Connectors
     * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
     * @license  Proprietary
     * @link     http://no.tld
     */
    class DynamoDB extends AppUsage
    {
        private $connection;
        private string $tag;
        private array $params;

        /**
         * Constructor
         *
         * @param string|null $tag    Connection identifier
         * @param array       $params Connection parameters
         */
        public function __construct(?string $tag = null, array $params = [])
        {
            if ($tag !== null) {
                $this->tag = $tag;
                $this->params = $params;
                $this->open();
            }
        }

        /**
         * Get the DynamoDB connection
         *
         * @return mixed
         */
        public function getConnection()
        {
            return $this->connection;
        }

        /**
         * Set the DynamoDB connection
         *
         * @param mixed $connection DynamoDB connection
         * @return void
         */
        public function setConnection($connection): void
        {
            $this->connection = $connection;
        }

        /**
         * Open connection to DynamoDB
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
         * Batch get items from DynamoDB
         *
         * @param string $table Table name
         * @param array  $keys  Keys to fetch
         * @return array
         */
        public function batchGet(string $table, array $keys): array
        {
            $this->open();

            $marshaler = new Marshaler();

            $keysToGet = [];
            foreach ($keys as $keyName => $keyValues) {
                foreach ($keyValues as $keyValue) {
                    $keysToGet[] = $marshaler->marshalJson('{"' . $keyName . '": ' . json_encode($keyValue) . '}');
                }
            }

            $request = [
                'RequestItems' => [
                    $table => [
                        'Keys' => $keysToGet,
                    ],
                ],
            ];

            $result = $this->connection->batchGetItem($request);

            $items = $result['Responses'][$table];
            $final = [];
            foreach ($items as $item) {
                $final[] = $marshaler->unmarshalItem($item);
            }

            return $final;
        }

        /**
         * Get a single item from DynamoDB
         *
         * @param string $table Table name
         * @param array  $keys  Keys to fetch
         * @return array|false
         */
        public function get(string $table, array $keys): array|false
        {
            $this->open();

            $marshaler = new Marshaler();

            $result = $this->connection->getItem([
                'TableName' => $table,
                'ConsistentRead' => true,
                'Key' => $marshaler->marshalItem($keys),
            ]);

            return isset($result['Item']) ? $marshaler->unmarshalItem($result['Item']) : false;
        }

        /**
         * Query items from DynamoDB
         *
         * @param string      $table    Table name
         * @param string|bool $index    Index name or false
         * @param array       $keys     Query keys
         * @param int|null    $pageSize Page size for pagination
         * @return array
         */
        public function query(string $table, string|bool $index = false, array $keys = [], ?int $pageSize = null): array
        {
            $this->open();

            $marshaler = new Marshaler();

            $keyConditionExpression = [];
            $expressionAttributeValues = [];
            $expressionAttributeNames = [];

            foreach ($keys as $ind => $val) {
                $keyConditionExpression[] = "#alias_$ind = :$ind";
                $expressionAttributeValues[":$ind"] = $val;
                $expressionAttributeNames["#alias_$ind"] = $ind;
            }

            $expression = ['TableName' => $table];

            if (!empty($keys)) {
                $expression = array_merge($expression, [
                    'KeyConditionExpression' => implode(' AND ', $keyConditionExpression),
                    'ExpressionAttributeValues' => $marshaler->marshalItem($expressionAttributeValues),
                    'ExpressionAttributeNames' => $expressionAttributeNames
                ]);
            }

            if ($index) {
                $expression['IndexName'] = $index;
            }

            $resultItems = [];

            do {
                if ($pageSize !== null) {
                    $expression['Limit'] = $pageSize;
                }

                $result = !empty($keys) ? $this->connection->query($expression) : $this->connection->scan($expression);

                if (isset($result['Items'])) {
                    $resultItems = array_merge($resultItems, $result['Items']);
                }

                $expression['ExclusiveStartKey'] = $result['LastEvaluatedKey'] ?? null;
            } while ($expression['ExclusiveStartKey'] !== null);

            return $resultItems;
        }

        /**
         * Insert an item into DynamoDB
         *
         * @param string $table   Table name
         * @param array  $payload Item to insert
         * @return array
         */
        public function insert(string $table, array $payload): \Aws\Result
        {
            $this->open();

            $marshaler = new Marshaler();

            return $this->connection->putItem([
                'TableName' => $table,
                'Item' => $marshaler->marshalItem($payload),
            ]);
        }

        /**
         * Update an item in DynamoDB
         *
         * @param string $table   Table name
         * @param array  $keys    Keys to identify the item
         * @param array  $payload Data to update
         * @return array
         */
        public function update(string $table, array $keys, array $payload): \Aws\Result
        {
            $this->open();

            $marshaler = new Marshaler();
            $updateExpression = [];
            $removeExpression = [];
            $expressionAttributeValues = [];
            $expressionAttributeNames = [];

            foreach ($payload as $ind => $val) {
                $i = count($updateExpression) + count($removeExpression) + 1;
                if ($val === null) {
                    $removeExpression[] = "#delAttr$i";
                    $expressionAttributeNames["#delAttr$i"] = $ind;
                } else {
                    $updateExpression[] = "#setAttr$i = :setAttr$i";
                    $expressionAttributeValues[":setAttr$i"] = $val;
                    $expressionAttributeNames["#setAttr$i"] = $ind;
                }
            }

            $finalExpression = [];
            if (!empty($updateExpression)) {
                $finalExpression[] = "SET " . implode(", ", $updateExpression);
            }
            if (!empty($removeExpression)) {
                $finalExpression[] = "REMOVE " . implode(", ", $removeExpression);
            }

            return $this->connection->updateItem([
                'TableName' => $table,
                'Key' => $marshaler->marshalItem($keys),
                'UpdateExpression' => implode(" ", $finalExpression),
                'ExpressionAttributeNames' => $expressionAttributeNames,
                'ExpressionAttributeValues' => $marshaler->marshalItem($expressionAttributeValues),
                'ReturnValues' => 'ALL_NEW'
            ]);
        }

        /**
         * Delete an item from DynamoDB
         *
         * @param string $table Table name
         * @param array  $keys  Keys to identify the item
         * @return array
         */
        public function delete(string $table, array $keys): \Aws\Result
        {
            $this->open();

            $marshaler = new Marshaler();

            return $this->connection->deleteItem([
                'TableName' => $table,
                'Key' => $marshaler->marshalItem($keys)
            ]);
        }
    }
}
