<?php

declare(strict_types=1);

namespace core\base {

    /**
     * Class for sanitizing domain objects
     *
     * @category Core
     * @package  Core\Base
     * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
     * @license  Proprietary
     */
    abstract class Sanitization
    {
        /**
         * Sanitize using a callable function
         *
         * @param string   $field    The field name
         * @param mixed    $value    The value to be sanitized
         * @param callable $callable The callable function
         *
         * @return mixed
         */
        public static function callable(string $field, $value, callable $callable)
        {
            return $callable($value);
        }

        /**
         * Sanitize alphanumeric string
         *
         * @param string $field  The field name
         * @param mixed  $value  The value to be sanitized
         * @param array  $params Additional parameters (not used)
         *
         * @return string|null
         */
        public static function alphaNum(string $field, $value, ?array $params = null): ?string
        {
            if (($value !== null || $value !== '') && is_string($value)) {
                // Remove HTML tags with UTF-8 mb4 support
                $sanitizedValue = preg_replace('/<[^>]*>/u', '', $value);

                // Remove non-alphanumeric characters
                $sanitizedValue = preg_replace("/[^0-9a-zA-ZáàâãéèêíïóôõöúçñÁÀÂÃÉÈÍÏÓÔÕÖÚÇÑ ]/u", "", $sanitizedValue);

                // Remove extra spaces and return the sanitized value
                return trim(preg_replace('/\s+/', ' ', $sanitizedValue));
            }

            return null;
        }

        /**
         * Sanitize safe string
         *
         * @param string $field  The field name
         * @param mixed  $value  The value to be sanitized
         * @param array  $params Additional parameters (not used)
         *
         * @return string|null
         */
        public static function safeString(string $field, $value, ?array $params = null): ?string
        {

            if (($value !== null || $value !== '') && is_string($value)) {

                // Remove HTML tags with UTF-8 mb4 support
                $sanitizedValue = preg_replace('/<[^>]*>/u', '', $value);

                // Remove non-alphanumeric characters, including some special characters
                $sanitizedValue = preg_replace("/[^0-9a-zA-ZáàâãéèêíïóôõöúçñÁÀÂÃÉÈÍÏÓÔÕÖÚÇÑ\s!@#$%^&*()\-_=+{}|[\]:;\",.<>?\/`~]/u", "", $sanitizedValue);

                // Remove extra spaces and return the sanitized value
                return trim(preg_replace('/\s+/', ' ', $sanitizedValue));
            }

            return null;
        }

        /**
         * Convert string to lowercase
         *
         * @param string $field  The field name
         * @param mixed  $value  The value to be sanitized
         * @param array  $params Additional parameters (not used)
         *
         * @return string|null
         */
        public static function lower(string $field, $value, ?array $params = null): ?string
        {
            return (($value !== null || $value !== '') && is_string($value)) ? trim(strip_tags(mb_strtolower($value, 'UTF-8'))) : null;
        }

        /**
         * Sanitize Brazilian phone number
         *
         * @param string $field  The field name
         * @param mixed  $value  The value to be sanitized
         * @param array  $params Additional parameters (not used)
         *
         * @return string|null
         */
        public static function phonebr(string $field, $value, ?array $params = null): ?string
        {
            if ($value === null || $value === '' || !is_string($value)) {
                return null;
            }

            // Remove all non-digit characters
            $phoneNumber = preg_replace('/[^0-9]/', '', trim($value));

            // Check phone number length
            $phoneNumberLength = strlen($phoneNumber);
            if ($phoneNumberLength < 10 || $phoneNumberLength > 13) {
                // Invalid phone number, return the original value
                return $phoneNumber;
            }

            $normalizedPhoneNumber = '';
            if (in_array($phoneNumberLength, [10, 11])) {
                $normalizedPhoneNumber = '+55 ';
                $normalizedPhoneNumber .= '(' . substr($phoneNumber, 0, 2) . ') ';
                $normalizedPhoneNumber .= ($phoneNumberLength == 10) ? substr($phoneNumber, 2, 4) . '-' : substr($phoneNumber, 2, 5) . '-';
                $normalizedPhoneNumber .= ($phoneNumberLength == 10) ? substr($phoneNumber, 6) : substr($phoneNumber, 7);
            } elseif (in_array($phoneNumberLength, [12, 13])) {
                $normalizedPhoneNumber = '+' . substr($phoneNumber, 0, 2) . ' ';
                $normalizedPhoneNumber .= '(' . substr($phoneNumber, 2, 2) . ') ';
                $normalizedPhoneNumber .= ($phoneNumberLength == 12) ? substr($phoneNumber, 4, 4) . '-' : substr($phoneNumber, 4, 5) . '-';
                $normalizedPhoneNumber .= ($phoneNumberLength == 12) ? substr($phoneNumber, 8) : substr($phoneNumber, 9);
            }

            return $normalizedPhoneNumber;
        }

        /**
         * Sanitize email address
         *
         * @param string $field  The field name
         * @param mixed  $value  The value to be sanitized
         * @param array  $params Additional parameters (not used)
         *
         * @return string|null
         */
        public static function email(string $field, $value, ?array $params = null): ?string
        {
            return (($value !== null || $value !== '') && is_string($value)) ? filter_var($value, FILTER_SANITIZE_EMAIL) : null;
        }

        /**
         * Sanitize CPF/CNPJ (Brazilian tax identification numbers)
         *
         * @param string $field  The field name
         * @param mixed  $value  The value to be sanitized
         * @param array  $params Additional parameters (not used)
         *
         * @return string|null
         */
        public static function cpfcnpj(string $field, $value, ?array $params = null): ?string
        {
            if ($value === null || $value === '' || !is_string($value)) {
                return null;
            }

            $documento = preg_replace('/[^0-9]/', '', $value);

            if (strlen($documento) == 11) {
                return substr($documento, 0, 3) . '.' . substr($documento, 3, 3) . '.' . substr($documento, 6, 3) . '-' . substr($documento, 9, 2);
            } elseif (strlen($documento) == 14) {
                return substr($documento, 0, 2) . '.' . substr($documento, 2, 3) . '.' . substr($documento, 5, 3) . '/' . substr($documento, 8, 4) . '-' . substr($documento, 12, 2);
            } else {
                return null;
            }
        }

        /**
         * Set default value if empty
         *
         * @param string $field  The field name
         * @param mixed  $value  The value to be sanitized
         * @param array  $params Additional parameters
         *
         * @return mixed
         */
        public static function defaultIfEmpty(string $field, $value, ?array $params = null)
        {
            return ($value === null || $value === '') ? $params['value'] ?? null : $value;
        }

        /**
         * Convert to boolean
         *
         * @param string $field  The field name
         * @param mixed  $value  The value to be sanitized
         * @param array  $params Additional parameters (not used)
         *
         * @return bool
         */
        public static function boolean(string $field, $value, ?array $params = null): bool
        {
            return ($value === null || $value === '') ? false : (bool) $value;
        }

        /**
         * Convert to integer
         *
         * @param string $field  The field name
         * @param mixed  $value  The value to be sanitized
         * @param array  $params Additional parameters (not used)
         *
         * @return int|null
         */
        public static function integer(string $field, $value, ?array $params = null): ?int
        {
            return ($value === null || $value === '') ? null : (int) $value;
        }

        /**
         * Truncate string
         *
         * @param string $field  The field name
         * @param mixed  $value  The value to be sanitized
         * @param array  $params Additional parameters
         *
         * @return string|null
         */
        public static function truncate(string $field, $value, ?array $params = null): ?string
        {

            if ($value === null || $value === '') {
                return null;
            }

            if (isset($params['length']) && is_numeric($params['length'])) {
                return substr((string)$value, 0, (int) $params['length']);
            }

            return null;
        }

        /**
         * Format currency
         *
         * @param string $field  The field name
         * @param mixed  $value  The value to be sanitized
         * @param array  $params Additional parameters (not used)
         *
         * @return float|null
         */
        public static function currency(string $field, $value, ?array $params = null): ?float
        {
            if ($value === null || $value === '') {
                return null;
            }
            return (float) number_format((float) $value, 2, '.', '');
        }
    }
}
