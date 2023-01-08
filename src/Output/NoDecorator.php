<?php declare(strict_types=1);

namespace SouthPointe\Cli\Output;

use const PHP_EOL;

readonly class NoDecorator implements Decorator
{
    /**
     * @inheritDoc
     */
    public function newLine(): string
    {
        return PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function text(string $text): string
    {
        return $this->text($text);
    }

    /**
     * @inheritDoc
     */
    public function debug(string $text): string
    {
        return $this->text($text);
    }

    /**
     * @inheritDoc
     */
    public function info(string $text): string
    {
        return $this->text($text);
    }

    /**
     * @inheritDoc
     */
    public function notice(string $text): string
    {
        return $this->text($text);
    }

    /**
     * @inheritDoc
     */
    public function warning(string $text): string
    {
        return $this->text($text);
    }

    /**
     * @inheritDoc
     */
    public function error(string $text): string
    {
        return $this->text($text);
    }

    /**
     * @inheritDoc
     */
    public function critical(string $text): string
    {
        return $this->text($text);
    }

    /**
     * @inheritDoc
     */
    public function alert(string $text): string
    {
        return $this->text($text);
    }
}
