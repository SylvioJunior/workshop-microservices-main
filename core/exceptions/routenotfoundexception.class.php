<?php

declare(strict_types=1);

namespace core\exceptions {

    use Exception;

    /**
     * RouteNotFoundException
     *
     * Exception thrown when a route is not found.
     *
     * @category Core
     * @package  Core\Exceptions
     * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
     * @license  Proprietary
     */
    class RouteNotFoundException extends Exception implements AppException
    {
        /**
         * @var string The default error message
         */
        protected $message = "Route not found";

        /**
         * @var int The HTTP response code
         */
        protected $httpResponse = 404;

        /**
         * Get exception details
         *
         * @return array<string, int|string> Exception details
         */
        public function getDetails(): array
        {
            return [
                'status' => $this->httpResponse,
                'data' => $this->getMessage()
            ];
        }
    }
}
