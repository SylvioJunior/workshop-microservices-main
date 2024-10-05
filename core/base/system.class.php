<?php

declare(strict_types=1);

namespace core\base {

    /**
     * System class for managing application session data
     *
     * This abstract class provides functionality for executing system commands.
     *
     * @category Core
     * @package  Core\Base
     * @author   Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
     * @license  Proprietary
     */
    abstract class System
    {
        /**
         * Execute a system command
         *
         * @param string $command The command to execute
         * @param string $input   Optional input to pass to the command
         *
         * @return array|false An array containing the command's return value, output, and error, or false on failure
         */
        public static function exec(string $command, string $input = ''): array|false
        {
            $descriptorSpec = [
                0 => ["pipe", "r"],  // stdin
                1 => ["pipe", "w"],  // stdout
                2 => ["pipe", "w"]   // stderr
            ];

            $process = proc_open($command, $descriptorSpec, $pipes);

            if (is_resource($process)) {
                fwrite($pipes[0], $input);
                fclose($pipes[0]);

                $output = stream_get_contents($pipes[1]);
                fclose($pipes[1]);

                $error = stream_get_contents($pipes[2]);
                fclose($pipes[2]);

                $returnValue = proc_close($process);

                return [
                    'return' => $returnValue,
                    'output' => $output,
                    'error' => $error
                ];
            }

            return false;
        }
    }
}
