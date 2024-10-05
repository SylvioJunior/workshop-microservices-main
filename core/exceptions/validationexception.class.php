<?php

declare(strict_types=1);

namespace core\exceptions {

    use Exception;

    /**
     * ValidationException class
     *
     * Represents an exception for validation errors in the application.
     */
    class ValidationException extends Exception implements AppException
    {
        /**
         * Get exception details
         *
         * @return array<string, int|array<string, mixed>> Exception details
         */
        public function getDetails(): array
        {
            return [
                'status' => 409,
                'data' => json_decode($this->getMessage(), true) ?? []
            ];
        }
    }
}
