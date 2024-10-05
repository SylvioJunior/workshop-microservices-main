<?php

declare(strict_types=1);

namespace core\exceptions {

    use Exception;

    /**
     * GenericException class
     *
     * Represents a generic exception in the application.
     */
    class GenericException extends Exception implements AppException
    {
        /**
         * @var int HTTP response code
         */
        protected int $httpResponse = 500;

        /**
         * Get exception details
         *
         * @return array<string, int|string> Exception details
         */
        public function getDetails(): array
        {
            return [
                'status' => $this->httpResponse,
                'message' => $this->getMessage(),
            ];
        }
    }
}
