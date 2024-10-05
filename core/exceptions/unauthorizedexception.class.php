<?php

declare(strict_types=1);

namespace core\exceptions {

    use Exception;

    /**
     * UnauthorizedException class
     *
     * Represents an exception when access is not authorized in the application.
     */
    class UnauthorizedException extends Exception implements AppException
    {
        /**
         * @var int HTTP response code
         */
        protected int $httpResponse = 401;

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
