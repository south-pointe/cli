<?php declare(strict_types=1);

namespace SouthPointe\Cli;

use SouthPointe\Cli\Output\Decorator;
use SouthPointe\Stream\StreamWritable;
use function implode;

class Output
{
    /**
     * @param StreamWritable $stdout
     * @param StreamWritable $stderr
     * @param Decorator $decorator
     */
    public function __construct(
        readonly public StreamWritable $stdout,
        readonly public StreamWritable $stderr,
        public readonly Decorator $decorator,
    )
    {
    }

    /**
     * @param string ...$text
     * @return $this
     */
    protected function toStdout(string ...$text): static
    {
        $this->stdout->write(implode('', $text));
        return $this;
    }

    /**
     * @param string ...$text
     * @return $this
     */
    protected function toStderr(string ...$text): static
    {
        $this->stderr->write(implode('', $text));
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function text(string $text): static
    {
        return $this->toStdout(
            $this->decorator->text($text),
        );
    }

    /**
     * @param string|null $text
     * @return $this
     */
    public function line(?string $text = null): static
    {
        return $this->toStdout(
            $this->decorator->text($text ?? ''),
            $this->decorator->newLine(),
        );
    }

    /**
     * @param string $text
     * @return $this
     */
    public function debug(string $text): static
    {
        return $this->toStdout(
            $this->decorator->debug($text),
            $this->decorator->newLine(),
        );
    }

    /**
     * @param string $text
     * @return $this
     */
    public function info(string $text): static
    {
        return $this->toStdout(
            $this->decorator->info($text),
            $this->decorator->newLine(),
        );
    }

    /**
     * @param string $text
     * @return $this
     */
    public function notice(string $text): static
    {
        return $this->toStdout(
            $this->decorator->notice($text),
            $this->decorator->newLine(),
        );
    }

    /**
     * @param string $text
     * @return $this
     */
    public function warning(string $text): static
    {
        return $this->toStderr(
            $this->decorator->warning($text),
            $this->decorator->newLine(),
        );
    }

    /**
     * @param string $text
     * @return $this
     */
    public function error(string $text): static
    {
        return $this->toStderr(
            $this->decorator->error($text),
            $this->decorator->newLine(),
        );
    }

    /**
     * @param string $text
     * @return $this
     */
    public function critical(string $text): static
    {
        return $this->toStderr(
            $this->decorator->critical($text),
            $this->decorator->newLine(),
        );
    }

    /**
     * @param string $text
     * @return $this
     */
    public function alert(string $text): static
    {
        return $this->toStderr(
            $this->decorator->alert($text),
            $this->decorator->newLine(),
        );
    }
}
