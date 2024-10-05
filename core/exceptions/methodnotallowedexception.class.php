<?php

declare(strict_types=1);

namespace core\exceptions {

    use Exception;

    /**
     * MethodNotAllowedException class
     *
     * Represents an exception when a method is not allowed for a specific URL.
     */
    class MethodNotAllowedException extends Exception implements AppException
    {
        /**
         * @var string The default error message
         */
        protected $message = "Method not allowed for this URL.";

        /**
         * @var int The HTTP response code
         */
        protected $httpResponse = 405;

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
