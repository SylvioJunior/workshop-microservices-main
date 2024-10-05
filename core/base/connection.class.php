<?php

declare(strict_types=1);

namespace core\base {

    /**
     * Class that controls connections to general data services
     *
     * @category Core
     * @package  Core\Base
     * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
     * @license  Proprietary
     */
    abstract class Connection
    {
        /**
         * @var array<string, array<string, object>> Stores connections
         */
        public static array $connections = [];

        /**
         * Opens a connection to the data service and stores it as a Singleton.
         * Attempts a new connection if the previous one is dropped.
         *
         * @param string $type Connection type
         * @param string $tag  Connection identifier
         *
         * @return object The connection object
         */
        public static function &open(string $type, string $tag): object
        {
            if (!isset(self::$connections[$type][$tag])) {
                // Initialize the connection if it doesn't exist
                $connectorClass = "\\core\\base\\connectors\\" . $type;
                self::$connections[$type][$tag] = new $connectorClass($tag);
            }

            return self::$connections[$type][$tag];
        }
    }
}
