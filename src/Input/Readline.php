<?php declare(strict_types=1);

namespace SouthPointe\Cli\Input;

use SouthPointe\Ansi\Stream;
use function assert;
use function grapheme_extract;
use function grapheme_strlen;
use function grapheme_substr;
use function in_array;
use function is_array;
use function mb_strlen;
use function mb_strwidth;
use function preg_match;
use function str_starts_with;
use function strlen;

final class Readline
{
    public const BOL = "\x01"; // ctrl+a
    public const EOL = "\x05"; // ctrl+e
    public const BACKSPACE = ["\x08", "\x7F", "\b"]; // ctrl+h, delete key
    public const DELETE = "\x04"; // ctrl+d
    public const CUT_TO_BOL = "\x15"; // ctrl+u
    public const CUT_TO_EOL = "\x0b"; // ctrl+k
    public const CUT_WORD = "\x17"; // ctrl+w
    public const PASTE = "\x19"; // ctrl+y
    public const CURSOR_FORWARD = ["\x06", "\e[C"]; // ctrl+f, right arrow
    public const CURSOR_BACK = ["\x02", "\e[D"]; // ctrl+b, left arrow
    public const END = ["\x00", "\x0a", "\x0d", "\r"]; // EOF, ctrl+j,  ctrl+m, carriage return
    public const CLEAR_SCREEN = "\f"; // ctrl+l
    public const NEXT_WORD = "\ef"; // option+f
    public const PREV_WORD = "\eb"; // option+b

    public function __construct(
        protected Stream $ansi,
        protected InputInfo $info,
    )
    {
    }

    /**
     * @param string $key
     * @return void
     */
    public function process(string $key): void
    {
        $info = $this->info;
        $buffer = $info->buffer;
        $point = $info->point;
        $end = $info->end;
        $size = grapheme_strlen($key);

        $info->latest = $key;

        if (self::matchesKey($key, self::BACKSPACE)) {
            if ($point > 0) {
                $info->point--;
                $info->end--;
                $info->buffer = self::substr($buffer, 0, $point - 1) . self::substr($buffer, $point);
            }
        }
        elseif (self::matchesKey($key, self::DELETE)) {
            if ($end > 0) {
                $info->end--;
                $info->buffer = self::substr($buffer, 0, $point) . self::substr($buffer, $point + 1);
            }
        }
        elseif (self::matchesKey($key, self::CUT_TO_BOL)) {
            $info->buffer = self::substr($buffer, $point);
            $info->clipboard = self::substr($buffer, 0, $point);
            $info->point = 0;
            $info->end = $end - $point;
        }
        elseif (self::matchesKey($key, self::CUT_TO_EOL)) {
            $info->buffer = self::substr($buffer, 0, $point);
            $info->clipboard = self::substr($buffer, $point);
        }
        elseif (self::matchesKey($key, self::CUT_WORD)) {
            $lookahead = $point - 1;
            $cursor = $point;
            while ($lookahead >= 0 && !self::isWord($buffer[$lookahead])) {
                --$cursor;
                --$lookahead;
            }
            while ($lookahead >= 0 && self::isWord($buffer[$lookahead])) {
                --$cursor;
                --$lookahead;
            }
            $info->buffer = self::substr($buffer, 0, $cursor) . self::substr($buffer, $point);
            $info->clipboard = self::substr($buffer, $cursor, $point - $cursor);
            $info->point = $cursor;
            $info->end -= $point - $cursor;
        }
        elseif (self::matchesKey($key, self::PASTE)) {
            $pasting = $info->clipboard;
            $info->buffer = self::substr($buffer, 0, $point) . $pasting . self::substr($buffer, $point);
            $move = grapheme_strlen($pasting);
            $info->point += $move;
            $info->end += $move;
        }
        elseif (self::matchesKey($key, self::CURSOR_FORWARD)) {
            if ($point < $end) {
                $info->point = $point + 1;
            }
        }
        elseif (self::matchesKey($key, self::CURSOR_BACK)) {
            if ($point > 0) {
                $info->point = $point - 1;
            }
        }
        elseif (self::matchesKey($key, self::BOL)) {
            $info->point = 0;
        }
        elseif (self::matchesKey($key, self::EOL)) {
            $info->point = $end;
        }
        elseif (self::matchesKey($key, self::END)) {
            $info->done = true;
        }
        elseif (self::matchesKey($key, self::CLEAR_SCREEN)) {
            $this->ansi
                ->eraseScreen()
                ->cursorPosition(1, 1)
                ->flush();
        }
        elseif (self::matchesKey($key, self::NEXT_WORD)) {
            $cursor = $point;
            while ($cursor < $end && !self::isWord($buffer[$cursor])) {
                ++$cursor;
            }
            while ($cursor < $end && self::isWord($buffer[$cursor])) {
                ++$cursor;
            }
            $info->point = $cursor;
        }
        elseif (self::matchesKey($key, self::PREV_WORD)) {
            $lookahead = $point - 1;
            while ($lookahead >= 0 && !self::isWord($buffer[$lookahead])) {
                --$info->point;
                --$lookahead;
            }
            while ($lookahead >= 0 && self::isWord($buffer[$lookahead])) {
                --$info->point;
                --$lookahead;
            }
        }
        elseif (str_starts_with($key, "\e")) {
            // do nothing
        }
        else {
            $info->buffer = self::substr($buffer, 0, $point) . $key . self::substr($buffer, $point);
            $info->point += $size;
            $info->end += $size;
        }

        $info->done
            ? $this->done()
            : $this->render();
    }

    /**
     * @return void
     */
    protected function render(): void
    {
        $this->ansi
            ->eraseLine()
            ->cursorBack(9999)
            ->text($this->getRenderingText())
            ->cursorBack(9999)
            ->cursorForward(self::calcCursorPosition($this->info))
            ->flush();
    }

    /**
     * @return void
     */
    protected function done(): void
    {
        $this->ansi
            ->lineFeed()
            ->flush();
    }

    /**
     * @return string
     */
    protected function getRenderingText(): string
    {
        return $this->info->prompt . $this->info->buffer;
    }

    /**
     * @param string $char
     * @return bool
     */
    protected static function isWord(string $char): bool
    {
        // match separators (\p{Z}) or symbols (\p{S})
        return !preg_match("/[\p{Z}\p{S}]/", $char);
    }

    protected static function substr(string $string, int $offset, ?int $length = null): string
    {
        $newStr = grapheme_substr($string, $offset, $length);
        assert($newStr !== false);
        return $newStr;
    }

    /**
     * @param string $key
     * @param string|list<string> $candidate
     * @return bool
     */
    protected static function matchesKey(string $key, string|array $candidate): bool
    {
        if (is_array($candidate)) {
            return in_array($key, $candidate, true);
        }
        return $key === $candidate;
    }

    /**
     * @param InputInfo $info
     * @return int
     */
    protected static function calcCursorPosition(InputInfo $info): int
    {
        $buffer = $info->buffer;
        $position = 0;
        $offset = 0;
        $bytes = strlen(self::substr($buffer, 0, $info->point));

        while ($offset < $bytes) {
            $char = grapheme_extract($buffer, 1, GRAPHEME_EXTR_COUNT, $offset, $offset);
            if ($char !== false) {
                $position += self::getStringWidth($char);
            }
        }

        return strlen($info->prompt) + $position;
    }

    /**
     * @param string $char
     * @return int
     */
    protected static function getStringWidth(string $char): int
    {
        // detect full-width characters
        // mb_strlen check is required since some emojis will return values greater than 1 with mb_strwidth.
        // Ex: mb_strwidth('👋🏻') will return 2 but should return 1.
        return (mb_strwidth($char) === 2 && mb_strlen($char) === 1)
            ? 2
            : 1;
    }
}
