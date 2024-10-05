<?php

declare(strict_types=1);

namespace core\exceptions {

    use Exception;

    /**
     * ItemNotFoundException class
     *
     * Represents an exception when an item is not found in the application.
     */
    class ItemNotFoundException extends Exception implements AppException
    {
        /**
         * Get exception details
         *
         * @return array<string, int|string> Exception details
         */
        public function getDetails(): array
        {
            return [
                'status' => 204,
                'data' => $this->getMessage()
            ];
        }
    }
}
