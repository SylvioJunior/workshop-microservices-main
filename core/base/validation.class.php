<?php

declare(strict_types=1);

namespace core\base {

    use DateTime;
    use Exception;

    /**
     * Class for validating data transfer objects
     *
     * @category Core
     * @package  Core\Base
     * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
     * @license  Proprietary
     */
    abstract class Validation
    {
        /**
         * Validate a value using a specified method
         *
         * @param string $methodName The validation method name
         * @param string $field      The field name
         * @param mixed  $value      The value to validate
         * @param array  $result     The result array to store validation errors
         * @param array|null $params Additional parameters for validation
         *
         * @throws Exception If the validation method doesn't exist
         */
        public static function validate(
            string $methodName,
            string $field,
            $value,
            array &$result,
            ?array $params = null
        ): void {
            $classPath = self::class;

            if (method_exists($classPath, $methodName)) {
                $valid = call_user_func_array(
                    [$classPath, $methodName],
                    [$value, $params]
                );

                if (!$valid) {
                    $result[$field][] = $params['msg'] ?? $methodName;
                }
            } else {
                throw new Exception("Validation method {$methodName} does not exist.");
            }
        }

        /**
         * Custom validation using a callable function
         *
         * @param mixed $value  The value to validate
         * @param callable|null $params The callable function for custom validation
         *
         * @return bool
         */
        public static function custom($value, ?callable $params = null): bool
        {
            if (is_callable($params)) {
                $res = $params($value);
                return $res['valid'] ?? false;
            }
            return false;
        }

        /**
         * Check if the value is not empty
         *
         * @param mixed $value The value to check
         *
         * @return bool
         */
        public static function notEmpty($value): bool
        {
            return $value !== null && $value !== '';
        }

        /**
         * Check if the value is a string
         *
         * @param mixed $value The value to check
         *
         * @return bool
         */
        public static function string($value): bool
        {
            return $value === null || $value === '' || is_string($value);
        }

        /**
         * Check if the value is a boolean
         *
         * @param mixed $value The value to check
         *
         * @return bool
         */
        public static function boolean($value): bool
        {
            return $value === null || $value === '' || is_bool($value);
        }

        /**
         * Validate a phone number
         *
         * @param mixed $value The value to validate
         *
         * @return bool
         */
        public static function phone($value): bool
        {
            if ($value === null || $value === '') {
                return true;
            }

            $phoneNumber = preg_replace('/[^0-9]/', '', (string)$value);

            if (!is_string($phoneNumber)) {
                return false;
            }

            $phoneNumberLength = strlen($phoneNumber);
            if ($phoneNumberLength < 10 || $phoneNumberLength > 15) {
                return false;
            }

            return ctype_digit($phoneNumber);
        }

        /**
         * Validate an email address
         *
         * @param mixed $value The value to validate
         *
         * @return bool
         */
        public static function email($value): bool
        {
            if ($value === null || $value === '') {
                return true;
            }

            return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        }

        /**
         * Validate if the value is in a list of allowed values
         *
         * @param mixed $value  The value to validate
         * @param array|null $params The allowed values
         *
         * @return bool
         */
        public static function enum($value, ?array $params = null): bool
        {
            if ($value === null || $value === '') {
                return true;
            }

            if (!isset($params['options'])) {
                return false;
            }

            $options = json_decode(str_replace("'", '"', $params['options']), true);
            return in_array($value, $options, true);
        }

        /**
         * Validate if the value is within a numeric interval
         *
         * @param mixed $value  The value to validate
         * @param array|null $params The interval parameters
         *
         * @return bool
         */
        public static function interval($value, ?array $params = null): bool
        {
            if ($value === null || $value === '') {
                return true;
            }

            if (!preg_match("/^-?\d*\.?\d+$/", (string)$value)) {
                return false;
            }

            if (
                (isset($params['min']) && bccomp((string)$value, (string)$params['min'], 2) === -1) ||
                (isset($params['max']) && bccomp((string)$value, (string)$params['max'], 2) === 1)
            ) {
                return false;
            }

            return true;
        }

        /**
         * Validate if the value is an integer
         *
         * @param mixed $value The value to validate
         *
         * @return bool
         */
        public static function integer($value): bool
        {

            if ($value === null || $value === '' || !$value) {
                return true;
            }

            return preg_match("/^[0-9]+$/", (string)$value) === 1;
        }

        /**
         * Validate if the value has a minimum length
         *
         * @param mixed $value  The value to validate
         * @param array|null $params The minimum length parameter
         *
         * @return bool
         */
        public static function minLength($value, ?array $params = null): bool
        {
            if ($value === null || $value === '') {
                return true;
            }

            return is_string($value) && isset($params['value']) && strlen($value) >= $params['value'];
        }

        /**
         * Validate if the value has a maximum length
         *
         * @param mixed $value  The value to validate
         * @param array|null $params The maximum length parameter
         *
         * @return bool
         */
        public static function maxLength($value, ?array $params = null): bool
        {
            if ($value === null || $value === '') {
                return true;
            }

            return is_string($value) && isset($params['value']) && strlen($value) <= $params['value'];
        }

        /**
         * Validate if the value is a list (array)
         *
         * @param mixed $value The value to validate
         *
         * @return bool
         */
        public static function list($value): bool
        {
            return $value === null || $value === '' || is_array($value);
        }

        /**
         * Validate if the value is a valid date
         *
         * @param mixed $value The value to validate
         *
         * @return bool
         */
        public static function date($value): bool
        {
            if ($value === null) {
                return true;
            }

            $date = DateTime::createFromFormat('Y-m-d', (string)$value);
            return $date && $date->format('Y-m-d') === $value;
        }

        /**
         * Validate if the value is a valid datetime
         *
         * @param mixed $value The value to validate
         *
         * @return bool
         */
        public static function dateTime($value): bool
        {
            if ($value === null) {
                return true;
            }

            $date = DateTime::createFromFormat('Y-m-d H:i:s', (string)$value);
            return $date && $date->format('Y-m-d H:i:s') === $value;
        }

        /**
         * Validate if the value is a valid ISO 8601 datetime
         *
         * @param mixed $value The value to validate
         *
         * @return bool
         */
        public static function dateTimeIso8601($value): bool
        {
            if ($value === null) {
                return true;
            }

            $dateTime = DateTime::createFromFormat(DateTime::ATOM, (string)$value);
            return $dateTime && $dateTime->format(DateTime::ATOM) === $value;
        }

        /**
         * Validate if the value is a strong password
         *
         * @param mixed $value The value to validate
         *
         * @return bool
         */
        public static function enforcedpassword($value): bool
        {
            if ($value === null || $value === '') {
                return true;
            }

            return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\d\s]).{8,}$/', (string)$value) === 1;
        }

        /**
         * Validate if the value is a valid URL
         *
         * @param mixed $value The value to validate
         *
         * @return bool
         */
        public static function url($value): bool
        {
            if ($value === null || $value === '') {
                return true;
            }

            if (!is_string($value)) {
                return false;
            }

            return filter_var($value, FILTER_VALIDATE_URL) !== false;
        }

        /**
         * Validate if the value is a valid CPF or CNPJ
         *
         * @param mixed $value The value to validate
         *
         * @return bool
         */
        public static function cpfcnpj($value): bool
        {
            if ($value === null || $value === '') {
                return true;
            }

            $documento = preg_replace('/[^0-9]/', '', (string)$value);

            if (strlen($documento) === 11) {
                return self::cpf($documento);
            } elseif (strlen($documento) === 14) {
                return self::cnpj($documento);
            }

            return false;
        }

        /**
         * Validate if the value is a valid CPF
         *
         * @param mixed $value The value to validate
         *
         * @return bool
         */
        public static function cpf($value): bool
        {
            if ($value === null || $value === '') {
                return true;
            }

            $cpf = preg_replace('/[^0-9]/', '', (string)$value);

            if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
                return false;
            }

            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf[$c] * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf[$c] != $d) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Validate if the value is a valid CNPJ
         *
         * @param mixed $value The value to validate
         *
         * @return bool
         */
        public static function cnpj($value): bool
        {
            if ($value === null || $value === '') {
                return true;
            }

            $cnpj = preg_replace('/[^0-9]/', '', (string)$value);

            if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1*$/', $cnpj)) {
                return false;
            }

            $cnpj = str_split($cnpj);

            for ($i = 0, $j = 5, $sum = 0; $i < 12; $i++) {
                $sum += $cnpj[$i] * $j;
                $j = ($j == 2) ? 9 : $j - 1;
            }
            $res = $sum % 11;

            if ($cnpj[12] != ($res < 2 ? 0 : 11 - $res)) {
                return false;
            }

            for ($i = 0, $j = 6, $sum = 0; $i < 13; $i++) {
                $sum += $cnpj[$i] * $j;
                $j = ($j == 2) ? 9 : $j - 1;
            }
            $res = $sum % 11;

            return $cnpj[13] == ($res < 2 ? 0 : 11 - $res);
        }

        /**
         * Validate if the value is a valid currency amount
         *
         * @param mixed $value The value to validate
         *
         * @return bool
         */
        public static function currency($value): bool
        {
            if ($value === null || $value === '') {
                return true;
            }

            if (!is_numeric($value)) {
                return false;
            }

            $valueString = (string)$value;
            if (strlen($valueString) > 16) {
                return false;
            }

            $decimalParts = explode('.', $valueString);
            if (count($decimalParts) > 1 && strlen($decimalParts[1]) > 2) {
                return false;
            }

            return true;
        }

        /**
         * Validate scalar types
         *
         * @param mixed $value  The value to validate
         * @param array|null $params The scalar type of the field
         *
         * @return bool
         */
        public static function scalarType(
            mixed $value,
            ?array $params = null
        ): bool {

            if ($value === null) {
                return true;
            }

            return match ($params['type']) {
                'string' => is_string($value),
                'int', 'integer' => is_int($value),
                'float' => is_int($value) || is_float($value),
                'bool', 'boolean' => is_bool($value),
                'array' => is_array($value),
                default => true,
            };
        }
    }
}
