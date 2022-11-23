<?php declare(strict_types=1);

namespace SouthPointe\Cli;

use SouthPointe\Cli\Output\Ansi;
use SouthPointe\Cli\Output\Ansi\Color;

class Output
{
    /**
     * @param Ansi $ansi
     */
    public function __construct(
        readonly public Ansi $ansi,
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
        $ansi = $this->ansi->buffer();

        if ($foreground !== null) {
            $ansi->foreground($foreground);
        }

        if ($background !== null) {
            $ansi->background($background);
        }

        $ansi->text($text)
            ->noStyle()
            ->flush();

        return $this;
    }

    /**
     * @param string|null $text
     * @return $this
     */
    public function line(?string $text = null): static
    {
        $text !== null
            ? $this->ansi->line($text)
            : $this->ansi->lineFeed();

        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function debug(string $text): static
    {
        $this->ansi
            ->buffer()
            ->foreground(Color::Gray)
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
            ->foreground(Color::Green)
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
            ->buffer()
            ->foreground(Color::Yellow)
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
            ->buffer()
            ->background(Color::Red)
            ->foreground(Color::White)
            ->line($text)
            ->noStyle()
            ->flush();

        return $this;
    }
}