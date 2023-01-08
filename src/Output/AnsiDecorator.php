<?php declare(strict_types=1);

namespace SouthPointe\Cli\Output;

use SouthPointe\Ansi\Buffer;
use SouthPointe\Ansi\Codes\Color;

readonly class AnsiDecorator implements Decorator
{
    public function __construct(
        private Buffer $buffer,
    )
    {
    }

    /**
     * @param string $text
     * @return string
     */
    public function debug(string $text): string
    {
        return $this->buffer
            ->fgColor(Color::Gray)
            ->line($text)
            ->resetStyle()
            ->extract();
    }

    /**
     * @param string $text
     * @return string
     */
    public function info(string $text): string
    {
        return $this->buffer
            ->line($text)
            ->extract();
    }

    /**
     * @param string $text
     * @return string
     */
    public function notice(string $text): string
    {
        return $this->buffer
            ->fgColor(Color::Green)
            ->line($text)
            ->resetStyle()
            ->extract();
    }

    /**
     * @param string $text
     * @return string
     */
    public function warning(string $text): string
    {
        return $this->buffer
            ->fgColor(Color::Yellow)
            ->line($text)
            ->resetStyle()
            ->extract();
    }

    /**
     * @param string $text
     * @return string
     */
    public function error(string $text): string
    {
        return $this->buffer
            ->fgColor(Color::Red)
            ->line($text)
            ->resetStyle()
            ->extract();
    }

    /**
     * @param string $text
     * @return string
     */
    public function critical(string $text): string
    {
        return $this->buffer
            ->bgColor(Color::Red)
            ->fgColor(Color::White)
            ->line($text)
            ->resetStyle()
            ->extract();
    }

    /**
     * @param string $text
     * @return string
     */
    public function alert(string $text): string
    {
        return $this->buffer
            ->bgColor(Color::Red)
            ->fgColor(Color::White)
            ->blink()
            ->line($text)
            ->resetStyle()
            ->extract();
    }
}
