<?php declare(strict_types=1);

namespace SouthPointe\Cli\Output;

interface Decorator
{
    /**
     * @param string $text
     * @return string
     */
    public function debug(string $text): string;

    /**
     * @param string $text
     * @return string
     */
    public function info(string $text): string;

    /**
     * @param string $text
     * @return string
     */
    public function notice(string $text): string;

    /**
     * @param string $text
     * @return string
     */
    public function warning(string $text): string;

    /**
     * @param string $text
     * @return string
     */
    public function error(string $text): string;

    /**
     * @param string $text
     * @return string
     */
    public function critical(string $text): string;

    /**
     * @param string $text
     * @return string
     */
    public function alert(string $text): string;
}
