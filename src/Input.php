<?php declare(strict_types=1);

namespace SouthPointe\Cli;

use Closure;
use RuntimeException;
use SouthPointe\Cli\Input\InputInfo;
use SouthPointe\Cli\Input\Readline;
use function array_key_exists;
use function array_keys;
use function array_map;
use function assert;
use function filter_var;
use function fopen;
use function grapheme_strlen;
use function is_resource;
use function max;
use function preg_match;
use function readline_callback_handler_install;
use function readline_callback_handler_remove;
use function shell_exec;
use function str_pad;
use function str_repeat;
use function stream_get_contents;
use function stream_select;
use function substr;
use function system;
use function trim;
use const FILTER_VALIDATE_INT;
use const PHP_INT_MAX;

class Input
{
    /**
     * @param Output $output
     */
    public function __construct(
        readonly protected Output $output,
    )
    {
    }

    /**
     * @param string $prompt
     * @return string
     */
    public function text(string $prompt = ''): string
    {
        return $this->readline($prompt);
    }

    public function integer(string $prompt = ''): ?int
    {
        $ansi = $this->output->stdout;
        $value = null;
        $this->readline($prompt, function(InputInfo $info) use ($ansi, &$value) {
            if ($info->done) {
                return false;
            }

            if (preg_match("/^[0-9]$/", $info->latest)) {
                $value .= $info->latest;
            } else {
                $ansi->bell();
            }

            $ansi
                ->cursorBack(9999)
                ->eraseToEndOfLine()
                ->text($info->prompt . $value)
                ->flush();

            return null;
        });

        if ($value === null) {
            return null;
        }

        $converted = filter_var($value, FILTER_VALIDATE_INT);

        // PHP converts all values greater than PHP_INT_MAX to PHP_INT_MAX
        // so check that string value does not overflow.
        if ($converted === false) {
            throw new RuntimeException('Integer overflow! allowed:±' . PHP_INT_MAX . ' given: ' . $value);
        }

        return $converted;
    }

    /**
     * @param array<array-key, string> $options
     * @return string
     */
    public function select(array $options): string
    {
        $maxStrLen = max(array_map(grapheme_strlen(...), array_keys($options))) ?: 0;

        $text = '';
        foreach ($options as $key => $value) {
            $text .= str_pad($key, $maxStrLen) . '. ' . $value;
        }

        $choice = $this->text($text);

        if (array_key_exists($choice, $options)) {
            return $choice;
        }

        throw new RuntimeException("Invalid input: '$choice'");
    }

    /**
     * @param string $message
     * [Optional] The confirmation message
     * @param bool|null $default
     * [Optional] Setting this to **true** will set default to YES,
     * NO if set to **false**,
     * no default will be set if set to **null**.
     * Defaults to **null**.
     * @return bool
     */
    public function confirm(string $message = '', ?bool $default = null): bool
    {
        $yes = 'y';
        $no = 'n';

        $text = "{$message}({$yes}/{$no}) ";
        if ($default !== null) {
            $text .= '[default: ' . ($default ? $yes : $no) . ']';
        }

        $input = $this->text($text . ': ');

        return match ($input) {
            $yes => true,
            $no => false,
            default => $default ?? throw new RuntimeException("Invalid input: '$input'"),
        };
    }

    /**
     * @param string $prompt
     * @return string
     */
    public function hidden(string $prompt = ''): string
    {
        $stty = trim((string) shell_exec('stty -g'));
        system("stty -echo");
        $input = $this->text($prompt);
        system("stty $stty");

        // HACK: Pressing enter with no input shows duplicated prompt
        // for some reason, so we have to add a line feed.
        $this->output->line();

        return $input;
    }

    /**
     * @param string $prompt
     * @param string $replacement
     * @return string
     */
    public function masked(string $prompt = '', string $replacement = '*'): string
    {
        return $this->readline($prompt, function (InputInfo $info) use ($prompt, $replacement) {
            $this->output->stdout
                // Clear all output up to the end of prompt text.
                ->cursorBack(9999)->eraseToEndOfLine()
                // Write replacement text (will set the cursor to the end).
                ->text($prompt . str_repeat($replacement, $info->end))
                // Set the cursor back to the offset position.
                ->cursorBack($info->end - $info->point)
                ->flush();
        });
    }

    /**
     * @param string $prompt
     * @param Closure(InputInfo): (mixed|false)|null $onKeyInput
     * Invoked for each key input. First argument contains the character read and
     * second argument contains a string of all the chars upto the current char.
     * @return string
     */
    public function readline(string $prompt = '', ?Closure $onKeyInput = null): string
    {
        $stream = $this->getInputStream();
        $info = new InputInfo($prompt);
        $readline = new Readline($this->output->stdout, $info);

        readline_callback_handler_install($prompt, static fn() => true);
        try {
            while (!$info->done) {
                $readline->process($this->waitForInput($stream));

                if ($onKeyInput !== null) {
                    if ($onKeyInput($info) === false) {
                        break;
                    }
                }
            }
        }
        finally {
            readline_callback_handler_remove();
        }

        return $info->buffer;
    }

    /**
     * @param resource $stream
     * @return string
     */
    protected function waitForInput(mixed $stream): string
    {
        $read = [$stream];
        $write = $except = null;
        stream_select($read, $write, $except, null);

        $char = stream_get_contents($stream, 1);

        if ($char === false) {
            return "\x04";
        }

        if ($char === "\e") {
            return $this->readEscapeSequences($stream, $char);
        }

        if (grapheme_strlen($char) === null) {
            return $this->readMultibytePortions($stream, $char);
        }

        return $char;
    }

    /**
     * @param resource $stream
     * @param string $input
     * @return string
     */
    protected function readMultibytePortions($stream, string $input): string
    {
        do {
            $char = stream_get_contents($stream, 1);
            if ($char === false) {
                break;
            }
            $input .= $char;
        }
        while(grapheme_strlen($input) === null);

        return $input;
    }

    /**
     * @param resource $stream
     * @param string $input
     * @return string
     */
    protected function readEscapeSequences($stream, string $input): string
    {
        $readByte = static fn(): string|false => stream_get_contents($stream, 1);

        $char = $readByte();
        $input .= $char;

        // CSI (Control Sequence Introducer)
        if ($char === '[') {
            if (($char = $readByte()) === false) {
                return $input;
            }
            while($char >= "\x30" && $char <= "\x3F") {
                $input .= $char;
                $char = $readByte();
            }
            while($char >= "\x20" && $char <= "\x2F") {
                $input .= $char;
                $char = $readByte();
            }
            if ($char >= "\x40" && $char <= "\x7E") {
                $input .= $char;
            }
        }
        // OSC (Operating System Command)
        elseif ($char === ']') {
            while(substr($input, -2) !== '\e\\') {
                $input .= $readByte();
            }
        }
        // SS2 or SS3 (Single Shifts)
        elseif ($char === 'N' || $char === 'O') {
            $input .= $readByte();
        }

        return $input;
    }

    /**
     * @return resource
     */
    protected function getInputStream(): mixed
    {
        $stream = fopen('php://stdin', 'r');
        assert(is_resource($stream));
        return $stream;
    }
}
