<?php

declare(strict_types=1);

namespace core\base {

    /**
     * Class that registers the autoloader for other classes
     *
     * This class registers an autoloader that automatically includes new files
     * containing classes that are called at runtime.
     *
     * @category Core
     * @package  Core\Base
     * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
     * @license  http://no.tld Proprietary License
     * @link     http://no.tld
     */
    abstract class Autoloader
    {
        /**
         * Registers the autoloader to load classes, including their respective files
         *
         * @return void
         */
        public static function register(): void
        {
            spl_autoload_register(
                function (string $className): void {
                    $className = str_replace("\\", DIRECTORY_SEPARATOR, $className);
                    $classFile = strtolower($className) . ".class.php";
                    $traitFile = strtolower($className) . ".trait.php";
                    $dtoFile = strtolower($className) . ".dto.php";
                    $interfaceFile = strtolower($className) . ".interface.php";

                    $files = [
                        ROOTPATH . $classFile,
                        ROOTPATH . $interfaceFile,
                        ROOTPATH . $traitFile,
                        ROOTPATH . $dtoFile
                    ];

                    foreach ($files as $file) {
                        if (file_exists($file)) {
                            include_once $file;
                            break;
                        }
                    }
                }
            );
        }
    }
}
