<?php

declare(strict_types=1);

namespace core\base {

    /**
     * Abstract class for application usage tracking
     */
    abstract class AppUsage
    {
        /**
         * Flag to enable/disable usage collection
         *
         * @var bool
         */
        public static bool $usageCollect = false;

        /**
         * Array to store usage statistics
         *
         * @var array<string, array<string, int>>
         */
        public static array $usageStatistics = [];

        /**
         * Array to store usage history
         *
         * @var array<string, array<string, array<string, array>>>
         */
        public static array $usageHistory = [];

        /**
         * Magic method to handle method calls
         *
         * @param string $name      The name of the method being called
         * @param array  $arguments An array of arguments passed to the method
         *
         * @return mixed
         * @throws \Exception If the method does not exist
         */
        public function __call(string $name, array $arguments): mixed
        {
            if (method_exists($this, "_{$name}")) {
                $class = get_called_class();

                if (self::$usageCollect) {

                    if (!isset(self::$usageStatistics[$class][$name])) {
                        self::$usageStatistics[$class][$name] = 1;
                    } else {
                        self::$usageStatistics[$class][$name]++;
                    }

                    self::$usageHistory[(string)microtime(true)][$class][$name] = $arguments;
                }

                return call_user_func_array([$this, "_{$name}"], $arguments);
            }

            throw new \Exception("Method {$name} does not exist in class " . get_class($this));
        }
    }
}
