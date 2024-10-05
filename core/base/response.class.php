<?php

declare(strict_types=1);

namespace core\base {

    /**
     * Response Control Class
     *
     * This class manages the response to the client, controlling rendering methods
     * and HTTP headers returned to the client.
     *
     * @category Core
     * @package  Core\Base
     * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
     * @license  Proprietary
     */
    abstract class Response
    {
        public const DEFAULT = 'DEFAULT'; // Default response type with standard header
        public const API = 'API'; // Response type with RESTful API header

        public static int $code;
        public static string $text;
        public static array $headers = [];
        public static array $cookies = [];

        /**
         * Set HTTP headers for REST API, enabling CORS and other API-specific settings
         *
         * @return void
         */
        public static function setRestHeaders(): void
        {
            self::$headers[] = 'Access-Control-Allow-Origin: *';
            self::$headers[] = 'Access-Control-Allow-Credentials: true';
            self::$headers[] = 'Access-Control-Allow-Headers: Content-Type,Authentication';
            self::$headers[] = 'Access-Control-Allow-Methods: POST, GET, PUT, DELETE';
            self::$headers[] = 'Access-Control-Max-Age: 86400';
        }

        /**
         * Render the HTTP response content and return it to the client
         *
         * @return string The rendered content
         */
        private static function renderHttp(): string
        {
            foreach (self::$headers as $header) {
                header($header);
            }

            foreach (self::$cookies as $cookie) {
                setcookie($cookie[0], $cookie[1], $cookie[2] ?? 0, $cookie[3] ?? '');
            }

            http_response_code(self::$code);

            return self::$text;
        }

        /**
         * Render the CMD response content
         *
         * @return string The rendered content
         */
        private static function renderCmd(): string
        {
            return self::$text;
        }

        /**
         * Render the response content based on the request type
         *
         * @param string $text    Response data
         * @param int    $code    HTTP response code
         * @param string $type    Response type (API or DEFAULT)
         * @param array|null $headers Additional headers
         * @return string The rendered content
         */
        public static function render(
            string $text,
            int $code = 200,
            string $type = self::API,
            ?array $headers = null
        ): string {
            self::$code = $code;
            self::$text = $text;

            if ($type === self::API) {
                self::setRestHeaders();
            } elseif ($headers !== null) {
                self::$headers = $headers;
            }

            return Request::$type === 'http' ? self::renderHttp() : self::renderCmd();
        }

        /**
         * Render a view with or without a layout
         *
         * @param string $file    Path to the view file
         * @param array  $vars    Variables to be used in the view
         * @param string|null $layout  Path to the layout file
         * @param int    $code    HTTP response code
         * @param array|null $headers Additional headers
         * @return void
         */
        public static function view(
            string $file,
            array $vars,
            ?string $layout = null,
            int $code = 200,
            ?array $headers = null
        ): void {
            self::$code = $code;
            self::$headers = $headers ?? [];

            foreach (self::$headers as $header) {
                header($header);
            }

            foreach (self::$cookies as $cookie) {
                setcookie($cookie[0], $cookie[1], $cookie[2] ?? 0, $cookie[3] ?? '');
            }

            http_response_code(self::$code);

            extract($vars);
            ob_start();
            include APPPATH . $file;

            if ($layout !== null) {
                $view = ob_get_clean();
                include APPPATH . $layout;
            } else {
                ob_end_flush();
            }
        }
    }
}
