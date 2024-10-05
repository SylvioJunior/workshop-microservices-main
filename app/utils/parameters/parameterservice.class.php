<?php

declare(strict_types=1);

namespace App\Utils\Parameters;

use App\Utils\Cache\CacheService;
use Core\Base\Connection;
use Exception;

/**
 * Class ParameterService
 *
 * Handles parameter loading, encryption, and decryption.
 */
abstract class ParameterService
{
    /**
     * Retrieve an environment variable or secret from cache/database.
     *
     * @param string $item The name of the environment variable or secret.
     * @return array The retrieved value and encryption status.
     * @throws Exception If the environment variable is not found.
     */
    private static function env(string $item): array
    {
        if (isset($_ENV[$item])) {
            return [
                'encrypted' => false,
                'value' => $_ENV[$item],
            ];
        }

        if (!isset($_ENV['env_ip_dynamo'])) {
            throw new Exception("Environment variable env_ip_dynamo not found");
        }

        $cacheKey = "core:{$item}";
        $cachedValue = CacheService::get($cacheKey, true);

        if ($cachedValue !== false) {
            return [
                'encrypted' => true,
                'value' => $cachedValue,
            ];
        }

        $connection = Connection::open('dynamodb', 'env_ip_dynamo');
        $result = $connection->get('secrets', ['name' => $item]);

        if (!$result) {
            throw new Exception("Environment variable {$item} not found");
        }

        CacheService::set($cacheKey, $result['value']);

        return [
            'encrypted' => true,
            'value' => $result['value'] ?? null,
        ];
    }

    /**
     * Load and process a parameter.
     *
     * @param string $item The name of the parameter to load.
     * @param array $args Arguments for parameter substitution.
     * @return mixed The processed parameter value.
     * @throws Exception If an error occurs during processing.
     */
    public static function load(string $item, array $args = []): mixed
    {
        try {
            $code = self::env($item);

            if (empty($code['value'])) {
                return false;
            }

            if ($code['encrypted']) {
                $code['value'] = self::decrypt($code['value']);
            }

            $code['value'] = self::substituteArguments($code['value'], $args);

            return eval($code['value']);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Substitute placeholders in a string with provided arguments.
     *
     * @param string $value The string containing placeholders.
     * @param array $args The arguments to substitute.
     * @return string The string with substituted values.
     */
    private static function substituteArguments(string $value, array $args): string
    {
        $pattern = '/(:?%)(.+)(:?%)/mU';
        preg_match_all($pattern, $value, $matches, PREG_SET_ORDER);

        $search = [];
        $replace = [];
        foreach ($matches as $match) {
            $search[] = $match[0];
            $replace[] = $args[$match[2]] ?? '';
        }

        return str_replace($search, $replace, $value);
    }

    /**
     * Encrypt a string.
     *
     * @param string $string The string to encrypt.
     * @return string The encrypted string.
     */
    public static function encrypt(string $string): string
    {
        $ciphering = "aes-256-cbc";
        $options = 0;
        $ivLength = openssl_cipher_iv_length($ciphering);
        $encryptionIv = openssl_random_pseudo_bytes($ivLength);

        $encryption = openssl_encrypt(
            $string,
            $ciphering,
            $_ENV['env_secrets_key'],
            $options,
            $encryptionIv
        );

        return base64_encode($encryptionIv . $encryption);
    }

    /**
     * Decrypt a string.
     *
     * @param string $hash The encrypted string to decrypt.
     * @return string|false The decrypted string or false on failure.
     */
    public static function decrypt(string $hash): string|false
    {
        $decoded = base64_decode($hash);
        $ciphering = 'aes-256-cbc';
        $ivLength = openssl_cipher_iv_length($ciphering);
        $encryptionIv = mb_substr($decoded, 0, $ivLength, '8bit');
        $encryption = mb_substr($decoded, $ivLength, null, '8bit');

        return openssl_decrypt(
            $encryption,
            $ciphering,
            $_ENV['env_secrets_key'],
            0,
            $encryptionIv
        );
    }
}
