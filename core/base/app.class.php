<?php

declare(strict_types=1);

namespace core\base {

    use app\utils\parameters\ParameterService;

    /**
     * App session data management
     *
     * This class manages the application's session data and configuration.
     *
     * @category Core
     * @package  Core\Base
     * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
     * @license  Proprietary
     */
    abstract class App
    {
        /** @var mixed|null User data */
        public static $user = null;

        /** @var array|null Application configuration */
        public static $configuration = null;

        /**
         * Load application configuration
         *
         * @param array $configuration Application configuration array
         * @return void
         */
        public static function load(array $configuration): void
        {
            self::$configuration = $configuration;
        }
    }
}
