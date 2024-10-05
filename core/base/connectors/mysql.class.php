<?php

declare(strict_types=1);

/**
 * MySQL Database Connection Layer
 *
 * PHP Version 8.1
 *
 * @category Core
 * @package  Core\Base\Connectors
 * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license  Proprietary
 * @link     http://no.tld
 */

namespace Core\Base\Connectors;

use PDO;
use PDOException;
use PDOStatement;
use Exception;

use App\Utils\Parameters\ParameterService;

/**
 * Class that controls queries and data persistence in MySQL databases
 *
 * @category Core
 * @package  Core\Base\Connectors
 * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license  Proprietary
 * @link     http://no.tld
 */
class MySQL
{
    private ?PDO $pdo = null;
    private string $tag;

    /**
     * MySQL constructor.
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
     * Changes the current database
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
                } catch (Exception $e) {
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
     * Executes an SQL statement and handles any thrown exceptions
     *
     * @param string $sql    SQL query to be executed
     * @param array  $params Query parameters
     * @return MySQLStatement
     * @throws Exception
     */
    public function query(string $sql, array $params = []): MySQLStatement
    {
        $executed = false;
        do {
            $this->open();
            $statement = $this->pdo->prepare($sql);
            $this->bindParameters($statement, $params);

            try {
                if (!$statement->execute()) {
                    throw new Exception(implode(" ", $statement->errorInfo()) . ":  " . $sql);
                }
                $executed = true;
            } catch (Exception $e) {
                if ($this->isConnectionError($e->getMessage())) {
                    $executed = false;
                    $statement = null;
                    usleep(500000); // Sleep for 0.5 seconds
                } else {
                    throw $e;
                }
            }
        } while (!$executed);

        return new MySQLStatement($statement);
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
            "Lost connection to MySQL server",
            "MySQL server has gone away",
            "Deadlock found"
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
    public function lastId(string $table, string $pk, string $tag): int|false
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
    public function lastInsertId(): string
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
        return $this->pdo->commit();
    }

    /**
     * Starts a new transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Rolls back the current transaction
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
}

/**
 * Wrapper class for PDOStatement
 */
class MySQLStatement
{
    private PDOStatement $stmt;

    /**
     * MySQLStatement constructor
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
     * @param string|null $type Fetch type ('all' for fetchAll, null for fetch)
     * @return array|false
     */
    public function fetch(?string $type = null): array|false
    {
        if ($type === 'all') {
            return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }
}
