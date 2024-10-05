<?php

declare(strict_types=1);

namespace core\base {

    use core\exceptions\MethodNotAllowedException;
    use core\exceptions\RouteNotFoundException;

    /**
     * Class that registers and routes API requests to their respective controllers
     *
     * @category Core
     * @package  Core\Base
     * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
     * @license  http://no.tld Proprietary License
     * @link     http://no.tld
     */
    abstract class Router
    {
        /**
         * @var array<string, array<string, array<string, string|null>>> Registered routes
         */
        public static array $registered = [];

        /**
         * Dispatches the route identified in the API URL and sends the request to its respective controller
         *
         * @param string $method The request method (POST, GET, PUT, DELETE, Other)
         * @param string|null $route The route identified in the API URL call
         *
         * @return mixed
         * @throws MethodNotAllowedException
         * @throws RouteNotFoundException
         */
        public static function dispatch(string $method, ?string $route): mixed
        {
            foreach (self::$registered as $registeredRoute => $item) {
                if ($route !== null && preg_match("/^" . preg_quote($registeredRoute, "/") . "$/", $route)) {
                    if (isset($item[$method])) {
                        $retMiddleware = null;
                        if ($item[$method]['middleware'] !== null) {
                            $retMiddleware = forward_static_call_array($item[$method]['middleware'], []);
                        }

                        return forward_static_call_array($item[$method]['class'], []);
                    }

                    throw new MethodNotAllowedException();
                }
            }

            throw new RouteNotFoundException();
        }

        /**
         * Magic method to register routes for different HTTP methods and special methods
         *
         * @param string $name The name of the called method (get, post, put, delete, patch, cmd, lambda, internal)
         * @param array $arguments The arguments passed to the method
         * @throws \BadMethodCallException If the called method is not supported
         */
        public static function __callStatic(string $name, array $arguments): void
        {
            $supportedMethods = ['get', 'post', 'put', 'delete', 'patch', 'cmd', 'lambda', 'internal'];

            if (!in_array($name, $supportedMethods)) {
                throw new \BadMethodCallException("Method '$name' not supported.");
            }

            $route = $arguments[0] ?? '';
            $class = $arguments[1] ?? [];
            $middleware = $arguments[2] ?? null;

            self::registerRoute($route, strtoupper($name), $class, $middleware);
        }

        /**
         * Registers a route for a specific method
         *
         * @param string $route The route to be used in the API URL call
         * @param string $method The HTTP method or special method (CMD, LAMBDA, INTERNAL)
         * @param array $class The controller class/method to be invoked
         * @param array|null $middleware The middleware class/method to be invoked
         */
        private static function registerRoute(string $route, string $method, array $class, ?array $middleware): void
        {
            self::$registered[$route][$method] = compact('class', 'middleware');
        }
    }
}
