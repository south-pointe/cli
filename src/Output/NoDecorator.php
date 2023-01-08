<?php declare(strict_types=1);

namespace SouthPointe\Cli\Output;

use const PHP_EOL;

readonly class NoDecorator implements Decorator
{
    /**
     * @param string $text
     * @return string
     */
    public function debug(string $text): string
    {
        return $this->line($text);
    }

    /**
     * @param string $text
     * @return string
     */
    public function info(string $text): string
    {
        return $this->line($text);
    }

    /**
     * @param string $text
     * @return string
     */
    public function notice(string $text): string
    {
        return $this->line($text);
    }

    /**
     * @param string $text
     * @return string
     */
    public function warning(string $text): string
    {
        return $this->line($text);
    }

    /**
     * @param string $text
     * @return string
     */
    public function error(string $text): string
    {
        return $this->line($text);
    }

    /**
     * @param string $text
     * @return string
     */
    public function critical(string $text): string
    {
        return $this->line($text);
    }

    /**
     * @param string $text
     * @return string
     */
    public function alert(string $text): string
    {
        return $this->line($text);
    }

    /**
     * @param string $text
     * @return string
     */
    protected function line(string $text): string
    {
        return $text . PHP_EOL;
    }
}
