<?php declare(strict_types=1);

namespace SouthPointe\Cli;

use SouthPointe\Ansi\Codes\Color;
use SouthPointe\Ansi\Stream;

class Output
{
    /**
     * @param Stream $stdout
     * @param Stream $stderr
     */
    public function __construct(
        readonly public Stream $stdout,
        readonly public Stream $stderr,
    )
    {
    }

    /**
     * @param string $text
     * @param Color|null $foreground
     * @param Color|null $background
     * @return $this
     */
    public function text(string $text, ?Color $foreground = null, ?Color $background = null): static
    {
        if ($foreground !== null) {
            $this->stdout->fgColor($foreground);
        }

        if ($background !== null) {
            $this->stdout->bgColor($background);
        }

        $this->stdout->text($text)
            ->resetStyle()
            ->flush();

        return $this;
    }

    /**
     * @param string|null $text
     * @return $this
     */
    public function line(?string $text = null): static
    {
        $buffer = $text !== null
            ? $this->stdout->line($text)
            : $this->stdout->lineFeed();
        $buffer->flush();

        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function debug(string $text): static
    {
        $this->stdout
            ->fgColor(Color::Gray)
            ->line($text)
            ->flush();

        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function info(string $text): static
    {
        return $this->line($text);
    }

    /**
     * @param string $text
     * @return $this
     */
    public function notice(string $text): static
    {
        $this->stdout
            ->fgColor(Color::Green)
            ->line($text)
            ->flush();

        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function warning(string $text): static
    {
        $this->stderr
            ->fgColor(Color::Yellow)
            ->line($text)
            ->flush();

        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function error(string $text): static
    {
        $this->stderr
            ->fgColor(Color::Red)
            ->line($text)
            ->resetStyle()
            ->flush();

        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function critical(string $text): static
    {
        $this->stderr
            ->bgColor(Color::Red)
            ->fgColor(Color::White)
            ->line($text)
            ->resetStyle()
            ->flush();

        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function alert(string $text): static
    {
        $this->stderr
            ->bgColor(Color::Red)
            ->fgColor(Color::White)
            ->blink()
            ->line($text)
            ->resetStyle()
            ->flush();

        return $this;
    }
}
