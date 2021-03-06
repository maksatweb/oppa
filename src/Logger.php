<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *    <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *    <http://www.gnu.org/licenses/gpl-3.0.txt>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Oppa;

use Oppa\Exception\InvalidConfigException;

/**
 * @package Oppa
 * @object  Oppa\Logger
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Logger
{
    /**
     * Levels.
     * @const int
     */
    public const ALL = 30, // FAIL | WARN | INFO | DEBUG,
                 FAIL = 2,
                 WARN = 4,
                 INFO = 8,
                 DEBUG = 16;

    /**
     * Level.
     * @var int
     */
    private $level = 0; // default=disabled

    /**
     * Directory.
     * @var string
     */
    private $directory;

    /**
     * Directory checked.
     * @var bool
     */
    private static $directoryChecked = false;

    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Set level.
     * @param  int $level
     * @return void
     */
    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    /**
     * Get level.
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Set directory.
     * @param  string $directory
     * @return void
     */
    public function setDirectory(string $directory): void
    {
        $this->directory = $directory;
    }

    /**
     * Get directory.
     * @return ?string
     */
    public function getDirectory(): ?string
    {
        return $this->directory;
    }

    /**
     * Check directory (if not exists create it).
     * @return bool
     * @throws Oppa\Exception\InvalidConfigException
     */
    public function checkDirectory(): bool
    {
        if (empty($this->directory)) {
            throw new InvalidConfigException(
                "Log directory is not defined in given configuration, ".
                "define it using 'query_log_directory' key to activate logging.");
        }

        // provide some performance escaping to call "is_dir" and "mkdir" functions
        self::$directoryChecked = self::$directoryChecked ?: is_dir($this->directory);
        if (!self::$directoryChecked) {
            self::$directoryChecked = mkdir($this->directory, 0644, true);

            // !!! notice !!!
            // set your log dir secure
            file_put_contents($this->directory .'/index.php',
                "<?php header('HTTP/1.1 403 Forbidden'); ?>");
            // this action is for only apache, see nginx configuration here:
            // http://nginx.org/en/docs/http/ngx_http_access_module.html
            file_put_contents($this->directory .'/.htaccess',
                "Order deny,allow\r\nDeny from all");
        }

        return self::$directoryChecked;
    }

    /**
     * Log.
     * @param  int    $level
     * @param  string $message
     * @return void|bool
     */
    public function log(int $level, string $message)
    {
        // no log command
        if (!$level || ($level & $this->level) == 0) {
            return;
        }

        // ensure log directory
        $this->checkDirectory();

        // prepare message prepend
        $levelText = '';
        switch ($level) {
            case self::FAIL:
                $levelText = 'FAIL';
                break;
            case self::INFO:
                $levelText = 'INFO';
                break;
            case self::WARN:
                $levelText = 'WARN';
                break;
            case self::DEBUG:
                $levelText = 'DEBUG';
                break;
        }

        // prepare message & message file
        $message = sprintf('[%s] %s >> %s', $levelText, date('D, d M Y H:i:s O'), trim($message) ."\n");
        $messageFile = sprintf('%s/%s.log', $this->directory, date('Y-m-d'));

        return error_log($message, 3, $messageFile);
    }
}
