<?php declare(strict_types=1);

namespace SouthPointe\Cli;

use SouthPointe\Cli\Parameters\Argument;
use SouthPointe\Cli\Parameters\Option;
use function array_key_exists;

class Parameters
{
    /**
     * @param array<string, Argument> $arguments
     * @param array<string, Option> $options
     */
    public function __construct(
        public readonly array $arguments,
        public readonly array $options,
    )
    {
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasArgument(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    /**
     * @param string $name
     * @return Argument
     */
    public function getArgument(string $name): Argument
    {
        return $this->arguments[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * @param string $name
     * @return Option
     */
    public function getOption(string $name): Option
    {
        return $this->options[$name];
    }
}
