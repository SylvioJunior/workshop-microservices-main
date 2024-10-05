<?php

declare(strict_types=1);

namespace core\exceptions {

    /**
     * Interface AppException
     *
     * Defines a common interface for application exceptions.
     */
    interface AppException
    {
        /**
         * Get the exception details.
         *
         * @return array<string, mixed> The exception details.
         */
        public function getDetails(): array;
    }
}
