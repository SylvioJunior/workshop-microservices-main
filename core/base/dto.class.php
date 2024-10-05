<?php

declare(strict_types=1);

namespace core\base {

    use core\exceptions\ValidationException;
    use Exception;
    use ReflectionClass;
    use ReflectionProperty;

    /**
     * Class for handling data transfer objects
     *
     * @category Core
     * @package  Core\Base
     * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
     * @license  Proprietary
     */
    class Dto
    {
        /**
         * @var array Raw data received
         */
        private array $rawData;

        /**
         * @var object|null Parent object reference
         */
        private ?object $parentObject;

        /**
         * Constructor
         *
         * @param array       $data             Input data
         * @param bool        $ignoreValidation Whether to ignore validation
         * @param object|null $parentObject     Parent object reference
         */
        public function __construct(
            array $data,
            bool $ignoreValidation = false,
            ?object &$parentObject = null
        ) {
            $this->rawData = $data;
            $this->parentObject = $parentObject;

            static::bind($this, $data, $ignoreValidation);
        }

        /**
         * Get raw data
         *
         * @return array
         */
        public function raw(): array
        {
            return $this->rawData;
        }

        /**
         * Get parent object
         *
         * @return object|null
         */
        public function parent(): ?object
        {
            return $this->parentObject;
        }

        /**
         * Magic method to customize var_dump output
         *
         * @return array
         */
        public function __debugInfo(): array
        {
            $objData = json_decode(json_encode($this), true);

            unset($objData['rawData'], $objData['parentObject']);

            return $objData;
        }

        /**
         * Bind data to object properties
         *
         * @param object $target           Target object
         * @param array  $variables        Input variables
         * @param bool   $ignoreValidation Whether to ignore validation
         *
         * @throws ValidationException
         */
        public static function bind(
            object &$target,
            array &$variables,
            bool $ignoreValidation = false
        ): void {
            $reflectionClass = new ReflectionClass($target);
            $reflectionClassName = $reflectionClass->getName();
            $properties = $reflectionClass->getProperties();
            $validationErrors = [];

            foreach ($properties as $property) {
                self::processProperty($property, $variables, $target, $reflectionClassName, $ignoreValidation, $validationErrors);
            }

            if (!empty($validationErrors) && !$ignoreValidation) {
                throw new ValidationException(json_encode($validationErrors));
            }
        }

        /**
         * Process a single property
         *
         * @param ReflectionProperty $property
         * @param array              $variables
         * @param object             $target
         * @param string             $reflectionClassName
         * @param bool               $ignoreValidation
         * @param array              $validationErrors
         */
        private static function processProperty(
            ReflectionProperty $property,
            array &$variables,
            object &$target,
            string $reflectionClassName,
            bool $ignoreValidation,
            array &$validationErrors
        ): void {

            $fieldName = (string)$property->getName();
            $fieldType = $property->getType() ? $property->getType()->getName() : null;

            $isScalarType = in_array($fieldType, [null, 'string', 'boolean', 'bool', 'float', 'integer', 'int', 'array', 'mixed']);

            $docComment = $property->getDocComment();
            $fieldValue = $variables[$fieldName] ?? null;
            $fieldSanitized = $fieldValue;
            /*if ($fieldName == 'amount') {
                if (is_string($fieldValue)) {
                    var_dump($fieldName, $fieldValue, $fieldType, debug_backtrace());
                }
            }*/
            if ($isScalarType) {
                self::processScalarFieldType($docComment, $fieldName, $fieldType, $fieldSanitized, $target, $reflectionClassName, $ignoreValidation, $validationErrors);
            } elseif (class_exists($fieldType)) {
                self::processClassFieldType($fieldType, $fieldName, $fieldSanitized, $target, $validationErrors);
            }
        }

        /**
         * Process field with null type
         *
         * @param string|false $docComment
         * @param string       $fieldName
         * @param mixed        $fieldSanitized
         * @param object       $target
         * @param string       $reflectionClassName
         * @param bool         $ignoreValidation
         * @param array        $validationErrors
         */
        private static function processScalarFieldType(
            $docComment,
            string $fieldName,
            string|null $fieldType,
            &$fieldSanitized,
            object &$target,
            string $reflectionClassName,
            bool $ignoreValidation,
            array &$validationErrors
        ): void {

            // Validate scalar types
            if ($fieldType !== null) {

                Validation::validate(
                    'scalarType',
                    $fieldName,
                    $fieldSanitized,
                    $validationErrors,
                    [
                        "type" => $fieldType,
                        "msg" => "The parameter '{$fieldName}' must be of type {$fieldType}"
                    ]
                );
            }

            if ($docComment) {
                preg_match_all('/@(Validation|Sanitization|CustomValidation|CustomSanitization|Dto)\\\\(\w+)(\([^)]*\))?/', $docComment, $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $className = $match[1];
                    $methodName = $match[2];
                    $paramString = $match[3] ?? '';

                    $functionParams = self::extractFunctionParams($paramString);

                    self::executeAnnotation($className, $methodName, $fieldName, $fieldSanitized, $target, $reflectionClassName, $ignoreValidation, $validationErrors, $functionParams);
                }
            }

            if (
                $fieldSanitized !== null && $fieldType !== null && in_array($fieldType, ['string', 'int', 'integer', 'float', 'bool', 'boolean', 'array'])
            ) {
                @settype($fieldSanitized, $fieldType);
            }

            $target->$fieldName = $fieldSanitized;
        }

        /**
         * Process field with class type
         *
         * @param string $fieldType
         * @param string $fieldName
         * @param mixed  $fieldSanitized
         * @param object $target
         * @param array  $validationErrors
         */
        private static function processClassFieldType(
            string $fieldType,
            string $fieldName,
            &$fieldSanitized,
            object &$target,
            array &$validationErrors
        ): void {
            if (is_object($fieldSanitized)) {
                $fieldSanitized = json_decode(json_encode($fieldSanitized), true);
            }

            try {
                $target->$fieldName = new $fieldType(
                    $fieldSanitized ?? [],
                    false,
                    $target
                );
            } catch (ValidationException $e) {
                $validationErrors[$fieldName] = $e->getDetails()['data'];
            }
        }

        /**
         * Extract function parameters from string
         *
         * @param string $paramString
         * @return array
         */
        private static function extractFunctionParams(string $paramString): array
        {
            $functionParams = [];
            preg_match_all('/(\w+)="([^"]+)":?(\w+)?/', $paramString, $paramMatches, PREG_SET_ORDER);

            foreach ($paramMatches as $paramMatch) {
                $functionParams[$paramMatch[1]] = self::castParamValue($paramMatch[2], $paramMatch[3] ?? '');
            }

            return $functionParams;
        }

        /**
         * Cast parameter value to appropriate type
         *
         * @param string $value
         * @param string $type
         * @return mixed
         */
        private static function castParamValue(string $value, string $type)
        {
            switch ($type) {
                case 'string':
                    return (string) $value;
                case 'int':
                    return (int) $value;
                case 'float':
                    return (float) $value;
                case 'boolean':
                    return ($value === 'true');
                default:
                    return $value;
            }
        }

        /**
         * Execute annotation based on class name
         *
         * @param string $className
         * @param string $methodName
         * @param string $fieldName
         * @param mixed  $fieldSanitized
         * @param object $target
         * @param string $reflectionClassName
         * @param bool   $ignoreValidation
         * @param array  $validationErrors
         * @param array  $functionParams
         *
         * @throws Exception
         */
        private static function executeAnnotation(
            string $className,
            string $methodName,
            string $fieldName,
            &$fieldSanitized,
            object &$target,
            string $reflectionClassName,
            bool $ignoreValidation,
            array &$validationErrors,
            array $functionParams
        ): void {
            switch ($className) {
                case 'Validation':
                    self::executeValidation($methodName, $fieldName, $fieldSanitized, $validationErrors, $functionParams, $ignoreValidation);
                    break;
                case 'Sanitization':
                    self::executeSanitization($methodName, $fieldName, $fieldSanitized, $functionParams);
                    break;
                case 'CustomValidation':
                    self::executeCustomValidation($methodName, $fieldName, $fieldSanitized, $target, $reflectionClassName, $validationErrors, $functionParams, $ignoreValidation);
                    break;
                case 'CustomSanitization':
                    self::executeCustomSanitization($methodName, $fieldSanitized, $target, $reflectionClassName, $functionParams);
                    break;
                case 'Dto':
                    self::executeDtoAnnotation($methodName, $fieldName, $fieldSanitized, $validationErrors, $functionParams);
                    break;
            }
        }

        /**
         * Execute validation
         *
         * @param string $methodName
         * @param string $fieldName
         * @param mixed  $fieldSanitized
         * @param array  $validationErrors
         * @param array  $functionParams
         * @param bool   $ignoreValidation
         */
        private static function executeValidation(
            string $methodName,
            string $fieldName,
            $fieldSanitized,
            array &$validationErrors,
            array $functionParams,
            bool $ignoreValidation
        ): void {
            if (!$ignoreValidation) {
                Validation::validate(
                    $methodName,
                    $fieldName,
                    $fieldSanitized,
                    $validationErrors,
                    $functionParams
                );
            }
        }

        /**
         * Execute sanitization
         *
         * @param string $methodName
         * @param string $fieldName
         * @param mixed  $fieldSanitized
         * @param array  $functionParams
         */
        private static function executeSanitization(
            string $methodName,
            string $fieldName,
            &$fieldSanitized,
            array $functionParams
        ): void {
            $classPath = "\\core\\base\\Sanitization";
            $fieldSanitized = call_user_func(
                [$classPath, $methodName],
                $fieldName,
                $fieldSanitized,
                $functionParams
            );
        }

        /**
         * Execute custom validation
         *
         * @param string $methodName
         * @param string $fieldName
         * @param mixed  $fieldSanitized
         * @param object $target
         * @param string $reflectionClassName
         * @param array  $validationErrors
         * @param array  $functionParams
         * @param bool   $ignoreValidation
         *
         * @throws Exception
         */
        private static function executeCustomValidation(
            string $methodName,
            string $fieldName,
            $fieldSanitized,
            object &$target,
            string $reflectionClassName,
            array &$validationErrors,
            array $functionParams,
            bool $ignoreValidation
        ): void {
            if (!$ignoreValidation) {
                if (method_exists($reflectionClassName, $methodName)) {
                    $customReturn = call_user_func_array(
                        [$target, $methodName],
                        [$fieldSanitized, $functionParams]
                    );

                    if ($customReturn !== true) {
                        $validationErrors[$fieldName][] = $customReturn;
                    }
                } else {
                    throw new Exception("Custom method {$methodName} does not exist.");
                }
            }
        }

        /**
         * Execute custom sanitization
         *
         * @param string $methodName
         * @param mixed  $fieldSanitized
         * @param object $target
         * @param string $reflectionClassName
         * @param array  $functionParams
         *
         * @throws Exception
         */
        private static function executeCustomSanitization(
            string $methodName,
            &$fieldSanitized,
            object &$target,
            string $reflectionClassName,
            array $functionParams
        ): void {
            if (method_exists($reflectionClassName, $methodName)) {
                $fieldSanitized = call_user_func_array(
                    [$target, $methodName],
                    [$fieldSanitized, $functionParams]
                );
            } else {
                throw new Exception("Custom method {$methodName} does not exist.");
            }
        }

        /**
         * Execute DTO annotation
         *
         * @param string $methodName
         * @param string $fieldName
         * @param mixed  $fieldSanitized
         * @param array  $validationErrors
         * @param array  $functionParams
         *
         * @throws Exception
         */
        private static function executeDtoAnnotation(
            string $methodName,
            string $fieldName,
            &$fieldSanitized,
            array &$validationErrors,
            array $functionParams
        ): void {
            if ($methodName === 'listOfObjects' && is_array($fieldSanitized)) {
                $objList = [];

                if (!class_exists($functionParams['class'])) {
                    throw new Exception("Class {$functionParams['class']} does not exist.");
                }

                foreach ($fieldSanitized as $i => $itemValue) {
                    try {
                        if (!is_array($itemValue)) {
                            throw new ValidationException(json_encode([$fieldName => "O campo deve ser do tipo lista"]));
                        }
                        $objList[] = new $functionParams['class']($itemValue);
                    } catch (ValidationException $e) {
                        $validationErrors[$fieldName][$i] = $e->getDetails()['data'];
                    }
                }

                $fieldSanitized = $objList;
            }
        }
    }
}
