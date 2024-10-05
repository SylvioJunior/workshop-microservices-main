<?php

declare(strict_types=1);

/**
 * Database connection layer - PostgreSQL
 *
 * PHP Version 8.1
 *
 * @category Core
 * @package  Core\Base\Connectors
 * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license  Proprietary License
 * @link     http://no.tld
 */

namespace core\base\connectors {

    use PDO;
    use PDOException;
    use PDOStatement;
    use Exception;
    use Throwable;
    use app\utils\parameters\ParameterService;

    /**
     * Class that controls queries and data persistence in PostgreSQL databases
     *
     * @category Core
     * @package  Core\Base\Connectors
     * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
     * @license  Proprietary License
     * @link     http://no.tld
     */
    class PostgreSQL extends \core\base\AppUsage
    {
        private ?PDO $pdo = null;
        private string $tag;
        private bool $inTransaction = false;

        /**
         * PostgreSQL constructor.
         *
         * @param string $tag Connection identifier
         */
        public function __construct(string $tag)
        {
            $this->tag = $tag;
            $this->open();
        }

        /**
         * Opens or checks the database connection
         *
         * @return void
         */
        private function open(): void
        {
            if ($this->pdo === null) {
                $this->init($this->tag);
            } else {
                try {
                    $this->pdo->query("SELECT 1");
                } catch (PDOException $e) {
                    $this->init($this->tag);
                }
            }
        }

        /**
         * Switches to a different database
         *
         * @param string $dbname Database name
         * @return void
         */
        public function use(string $dbname): void
        {
            $this->query(sprintf("USE %s", $dbname));
        }

        /**
         * Initializes the database connection and stores it as a Singleton
         *
         * @param string $tag Connection identifier
         * @return void
         * @throws Exception
         */
        public function init(string $tag): void
        {
            if ($this->pdo === null) {
                $connected = false;
                do {
                    try {
                        $this->pdo = ParameterService::load($tag);
                        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
                        $connected = true;
                    } catch (Throwable $e) {
                        if ($this->isConnectionError($e->getMessage())) {
                            $connected = false;
                            usleep(500000); // Sleep for 0.5 seconds
                        } else {
                            throw $e;
                        }
                    }
                } while (!$connected);
            }
        }

        /**
         * Executes an SQL statement and handles any thrown exception
         *
         * @param string $sql SQL query to be executed
         * @param array $params Query parameters
         * @param array|null $binding Additional bindings
         * @return PostgreSQLStatement
         * @throws Exception
         */
        public function _query(string $sql, array $params = [], ?array $binding = null): PostgreSQLStatement
        {
            $executed = false;
            do {
                $this->open();
                try {
                    $statement = $this->pdo->prepare($sql);
                    $this->bindParameters($statement, $params);
                    if (!$statement->execute($binding)) {
                        throw new Exception(implode(" ", $statement->errorInfo()) . ":  " . $sql);
                    }
                    $executed = true;
                } catch (Throwable $e) {
                    if ($this->isConnectionError($e->getMessage())) {
                        $executed = false;
                        $statement = null;
                        usleep(500000); // Sleep for 0.5 seconds
                    } else {
                        throw $e;
                    }
                }
            } while (!$executed);

            return new PostgreSQLStatement($statement);
        }

        /**
         * Binds parameters to the prepared statement
         *
         * @param PDOStatement $statement Prepared statement
         * @param array $params Parameters to bind
         * @return void
         */
        private function bindParameters(PDOStatement $statement, array $params): void
        {
            $types = [
                'int' => PDO::PARAM_INT,
                'str' => PDO::PARAM_STR,
            ];

            foreach ($params as $key => &$value) {
                [$type, $paramName] = explode(':', $key) + [1 => $key];
                $paramType = $value === null ? PDO::PARAM_NULL : ($types[$type] ?? PDO::PARAM_STR);
                $statement->bindParam(":{$paramName}", $value, $paramType);
            }
        }

        /**
         * Checks if the error message indicates a connection error
         *
         * @param string $message Error message
         * @return bool
         */
        private function isConnectionError(string $message): bool
        {
            $errorPatterns = [
                "Too many connections",
                "Too many connection",
                "Deadlock found",
                "SQLSTATE[26000]",
                "SQLSTATE[08006]",
                "Connection timed out",
                "Lost connection to PostgreSQL server",
                "SQLSTATE[08003]", // Connection does not exist
                "SQLSTATE[08001]", // Unable to connect to PostgreSQL server
                "SQLSTATE[08004]", // Rejected connection
                "SQLSTATE[08007]", // Transaction resolution unknown
                "SQLSTATE[57P01]", // Admin shutdown
                "SQLSTATE[57P02]", // Crash shutdown
                "SQLSTATE[57P03]"  // Cannot connect now
            ];

            foreach ($errorPatterns as $pattern) {
                if (strpos($message, $pattern) !== false) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Gets the last inserted ID for a specific table
         *
         * @param string $table Table name
         * @param string $pk Primary key column name
         * @param string $tag Connection identifier
         * @return int|false
         */
        public function _lastId(string $table, string $pk, string $tag): int|false
        {
            $query = $this->query("SELECT MAX($pk) as lastId FROM {$table}", []);
            $result = $query->fetch();

            return $result ? (int)$result['lastId'] : false;
        }

        /**
         * Gets the last inserted ID
         *
         * @return string
         */
        public function _lastInsertId(): string
        {
            return $this->pdo->lastInsertId();
        }

        /**
         * Commits the current transaction
         *
         * @return bool
         */
        public function commit(): bool
        {
            if ($this->inTransaction) {
                $this->inTransaction = false;
                return $this->pdo->commit();
            }
            return false;
        }

        /**
         * Starts a new transaction
         *
         * @return bool
         */
        public function beginTransaction(): bool
        {
            if (!$this->inTransaction) {
                $this->inTransaction = true;
                return $this->pdo->beginTransaction();
            }
            return false;
        }

        /**
         * Rolls back the current transaction
         *
         * @return bool
         */
        public function rollBack(): bool
        {
            if ($this->inTransaction) {
                $this->inTransaction = false;
                return $this->pdo->rollBack();
            }
            return false;
        }
    }

    /**
     * Wrapper class for PDOStatement
     */
    class PostgreSQLStatement
    {
        private PDOStatement $stmt;

        /**
         * PostgreSQLStatement constructor.
         *
         * @param PDOStatement $stmt PDOStatement object
         */
        public function __construct(PDOStatement $stmt)
        {
            $this->stmt = $stmt;
        }

        /**
         * Fetches the result
         *
         * @param string|null $type Fetch type
         * @return array|false
         */
        public function fetch(?string $type = null): array|false
        {
            if ($type === 'all') {
                return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                return $this->stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    }
}
