<?php

declare(strict_types=1);

namespace core\base {

    /**
     * Utility class containing various helper methods
     *
     * This abstract class provides static utility methods for string manipulation,
     * date conversion, and other common operations.
     *
     * @category Core
     * @package  Core\Base
     * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
     * @license  Proprietary
     */
    abstract class Utils
    {
        /**
         * Sanitize a string to contain only alphanumeric characters and spaces
         *
         * @param string|null $value The input string to sanitize
         * @return string|null The sanitized string or null if input is null
         */
        public static function alphaNum(?string $value): ?string
        {
            if ($value === null) {
                return null;
            }

            // Remove HTML tags with UTF-8 mb4 support
            $sanitizedValue = preg_replace('/<[^>]*>/u', '', $value);

            // Remove non-alphanumeric characters (including accented characters)
            $sanitizedValue = preg_replace("/[^0-9a-zA-ZáàâãéèêíïóôõöúçñÁÀÂÃÉÈÍÏÓÔÕÖÚÇÑ ]/u", "", $sanitizedValue);

            // Remove extra spaces and trim
            return trim(preg_replace('/\s+/', ' ', $sanitizedValue));
        }

        /**
         * Normalize a string by removing accents and special characters
         *
         * @param string $str The input string to normalize
         * @param bool $toLower Whether to convert the result to lowercase
         * @return string The normalized string
         */
        public static function normalizeString(string $str, bool $toLower = true): string
        {
            // Convert string to ASCII, removing accents
            $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);

            // Remove special characters and numbers, keeping only letters and hyphens
            $str = preg_replace("/[^a-zA-Z0-9\-]/u", '', $str);

            // Convert to lowercase if specified
            if ($toLower) {
                $str = strtolower($str);
            }

            return $str;
        }

        /**
         * Convert a date from DD/MM/YYYY format to YYYY-MM-DD format
         *
         * @param string $date The input date in DD/MM/YYYY format
         * @return string|null The converted date in YYYY-MM-DD format or null if invalid
         */
        public static function dateConvert(string $date): ?string
        {
            if (preg_match("/^(\d{2})\/(\d{2})\/(\d{4})$/", $date, $matches)) {
                $day = (int)$matches[1];
                $month = (int)$matches[2];
                $year = (int)$matches[3];

                if (checkdate($month, $day, $year)) {
                    $dateObj = \DateTime::createFromFormat('d/m/Y', $date);
                    return $dateObj->format('Y-m-d');
                }
            }

            return null;
        }

        /**
         * Extract all strings from a variable, including nested arrays
         *
         * @param mixed $variable The input variable to extract strings from
         * @return array An array of extracted strings
         */
        public static function getArrayStrings($variable): array
        {
            if (is_string($variable)) {
                return [$variable];
            }

            if (is_array($variable)) {
                $strings = [];
                foreach ($variable as $item) {
                    $strings = array_merge($strings, self::getArrayStrings($item));
                }
                return $strings;
            }

            return [];
        }

        /**
         * Convert a string representation of file size to bytes
         *
         * @param string|int $value The input value (e.g., "5M", "2G", 1024)
         * @return int The size in bytes
         */
        public static function convertToBytes($value): int
        {
            if (is_numeric($value)) {
                return (int)$value;
            }

            $value = strtolower(trim($value));
            $lastChar = substr($value, -1);
            $number = (int)substr($value, 0, -1);

            switch ($lastChar) {
                case 'g':
                    return $number * 1073741824;
                case 'm':
                    return $number * 1048576;
                case 'k':
                    return $number * 1024;
                default:
                    return (int)$value;
            }
        }
    }
}
