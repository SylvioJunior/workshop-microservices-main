<?php

declare(strict_types=1);

namespace core\base {

    /**
     * Base Service class
     *
     * This abstract class provides a foundation for service layer classes.
     *
     * @category Core
     * @package  Core\Base
     * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
     * @license  Proprietary
     */
    abstract class Service
    {
        /** @var mixed|null Workspace context */
        protected static $workspace;

        /** @var mixed|null User context */
        protected static $user;

        /**
         * Set the context for the service
         *
         * @param mixed $workspace Workspace context
         * @param mixed $user User context
         * @return void
         */
        public static function setContext($workspace, $user): void
        {
            self::$workspace = $workspace;
            self::$user = $user;
        }
        /**
         * Get the current workspace ID
         *
         * @return string|null The UUID of the current workspace, or null if not set
         */
        public static function getWorkspaceId(): ?string
        {
            return self::$workspace->uuid ?? null;
        }

        /**
         * Get the current user ID
         *
         * @return string|null The UUID of the current user, or null if not set
         */
        public static function getUserId(): ?string
        {
            return self::$user->uuid ?? null;
        }
    }
}
