<?php declare(strict_types=1);

namespace SouthPointe\Cli;

use SouthPointe\Ansi\Codes\Color;
use SouthPointe\Ansi\Stream;

class Output
{
    /**
     * @param Stream $ansi
     */
    public function __construct(
        readonly public Stream $ansi,
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
            $this->ansi->fgColor($foreground);
        }

        if ($background !== null) {
            $this->ansi->bgColor($background);
        }

        $this->ansi->text($text)
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
            ? $this->ansi->line($text)
            : $this->ansi->lineFeed();
        $buffer->flush();

        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function debug(string $text): static
    {
        $this->ansi
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
        $this->ansi
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
        $this->ansi
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
        $this->ansi
            ->bgColor(Color::Red)
            ->fgColor(Color::White)
            ->line($text)
            ->resetStyle()
            ->flush();

        return $this;
    }
}
