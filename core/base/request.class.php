<?php

declare(strict_types=1);

namespace Core\Base;

use Core\Exceptions\ValidationException;
use ReflectionClass;
use ReflectionProperty;

/**
 * Class Request
 * 
 * Manages client request data.
 *
 * @package Core\Base
 */
abstract class Request
{
    public static string $type;
    public static string $userAgent;
    public static string $userIp;
    public static string $method;

    public static array $headers;

    public static mixed $payload;
    public static mixed $data;
    public static mixed $user = null;
    public static mixed $workspace = null;

    public static string $workspaceUuid;
    public static string $authorization;

    /**
     * Loads request information, headers, and other data.
     *
     * @param string $type      Type of request (http or cmd)
     * @param string $userAgent User-Agent of the client making the request
     * @param string $userIp    Client's IP address
     * @param string $method    Request method (POST, GET, PUT, DELETE, Other)
     * @param mixed  $data      Request parameters
     * @param mixed  $payload   JSON payload of the request
     * @param array  $headers   Request headers
     *
     * @return void
     */
    public static function load(
        string $type,
        string $userAgent,
        string $userIp,
        string $method,
        mixed $data,
        mixed $payload,
        array $headers
    ): void {
        self::$type = $type;
        self::$userAgent = $userAgent;
        self::$userIp = $userIp;
        self::$method = $method;
        self::$payload = $payload;
        self::$data = $data;
        self::$headers = $headers;

        self::$authorization = self::extractAuthorization($headers);
        self::$workspaceUuid = $headers['HTTP_X_WORKSPACE'] ?? $headers['x-workspace'] ?? '';
    }

    /**
     * Extracts the authorization token from headers.
     *
     * @param array $headers
     * @return string
     */
    private static function extractAuthorization(array $headers): string
    {
        if (isset($headers['HTTP_AUTHORIZATION'])) {
            return str_replace("Bearer ", "", $headers['HTTP_AUTHORIZATION']);
        }
        if (isset($headers['authorization'])) {
            return str_replace("Bearer ", "", $headers['authorization']);
        }
        return '';
    }

    /**
     * Retrieves all request data.
     *
     * @return array
     */
    public static function getRequestData(): array
    {
        return self::getStaticProperties();
    }

    /**
     * Captura o JWT do usuário.
     *
     * @return string|null O JWT do usuário ou null se não estiver presente
     */
    public static function getAuthenticationJwt(): ?string
    {
        if (!empty(self::$authorization)) {
            return self::$authorization;
        }

        $headers = self::$headers;

        if (isset($headers['HTTP_AUTHORIZATION'])) {
            return str_replace("Bearer ", "", $headers['HTTP_AUTHORIZATION']);
        }

        if (isset($headers['authorization'])) {
            return str_replace("Bearer ", "", $headers['authorization']);
        }

        return null;
    }

    /**
     * Retorna o UUID do workspace atual.
     *
     * @return string|null O UUID do workspace ou null se não estiver disponível
     */
    public static function getWorkspaceId(): ?string
    {
        if (isset(self::$workspace) && isset(self::$workspace->uuid)) {
            return self::$workspace->uuid;
        }

        $headers = self::$headers;

        if (isset($headers['x-workspace'])) {
            return $headers['x-workspace'];
        }

        if (isset($headers['HTTP_X_WORKSPACE'])) {
            return $headers['HTTP_X_WORKSPACE'];
        }

        return null;
    }

    /**
     * Retrieves safe request data, excluding sensitive information.
     *
     * @return array
     */
    public static function getSafeRequestData(): array
    {
        $requestData = self::getStaticProperties();
        unset($requestData['authorization'], $requestData['HTTP_AUTHORIZATION'], $requestData['user']);

        if (isset($requestData['headers'])) {
            $requestData['headers'] = array_filter($requestData['headers'], function ($headerName) {
                return str_contains($headerName, 'HTTP_') || str_contains($headerName, 'SERVER_') ||
                    str_contains($headerName, 'REQUEST_') || str_contains($headerName, 'REMOTE_');
            }, ARRAY_FILTER_USE_KEY);
        }

        return $requestData;
    }

    /**
     * Retrieves static properties of the class.
     *
     * @return array
     */
    private static function getStaticProperties(): array
    {
        $reflectionClass = new ReflectionClass(self::class);
        $requestData = [];

        foreach ($reflectionClass->getProperties(ReflectionProperty::IS_STATIC) as $property) {
            $propertyName = $property->getName();
            if ($property->isInitialized()) {
                $propertyValue = $property->getValue();
                $requestData[$propertyName] = $propertyValue;
            }
        }

        return $requestData;
    }

    /**
     * Validates variable types according to the provided specifications.
     *
     * @param array $specs Associative array where the key is the variable name and the value is the expected type or an array of types
     * @throws ValidationException If there are validation errors
     */
    public static function validateTypes(array $specs): void
    {
        $errors = [];

        foreach ($specs as $varName => $expectedType) {
            $dataValue = self::$data[$varName] ?? null;
            $payloadValue = self::$payload[$varName] ?? null;

            if ($dataValue !== null) {
                if (!Validation::scalarType($dataValue, ['type' => $expectedType])) {
                    $errors[$varName] = "O parâmetro '{$varName}' deve ser do tipo {$expectedType}";
                }
            }

            if ($payloadValue !== null) {
                if (!Validation::scalarType($payloadValue, ['type' => $expectedType])) {
                    $errors[$varName] = "O parâmetro '{$varName}' deve ser do tipo {$expectedType}";
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(json_encode($errors));
        }
    }
}
