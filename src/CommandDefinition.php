<?php declare(strict_types=1);

namespace SouthPointe\Cli;

use SouthPointe\Cli\Definitions\DefinedArgument;
use SouthPointe\Cli\Definitions\DefinedOption;
use function array_key_exists;
use function array_values;

class CommandDefinition
{
    /**
     * @var array<int, DefinedArgument>
     */
    protected array $argumentsByIndex;

    /**
     * @var array<string, DefinedArgument>
     */
    protected array $argumentsByName;

    /**
     * @param string $name
     * @param array<string, DefinedArgument> $arguments
     * @param array<string, DefinedOption> $longOptions
     * @param array<string, DefinedOption> $shortOptions
     */
    public function __construct(
        protected string $name,
        protected ?string $description,
        array $arguments,
        protected array $longOptions,
        protected array $shortOptions,
    )
    {
        $this->argumentsByIndex = array_values($arguments);
        $this->argumentsByName = $arguments;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<string, DefinedArgument>
     */
    public function getArguments(): array
    {
        return $this->argumentsByName;
    }

    /**
     * @param int $index
     * @return DefinedArgument|null
     */
    public function getArgumentByIndex(int $index): ?DefinedArgument
    {
        return $this->argumentsByIndex[$index] ?? null;
    }

    /**
     * @param string $name
     * @return DefinedArgument
     */
    public function getArgumentByName(string $name): DefinedArgument
    {
        return $this->argumentsByName[$name];
    }

    /**
     * @param string $name
     * @return DefinedOption
     */
    public function getLongOption(string $name): DefinedOption
    {
        return $this->longOptions[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function shortOptionExists(string $name): bool
    {
        return array_key_exists($name, $this->shortOptions);
    }

    /**
     * @param string $name
     * @return DefinedOption
     */
    public function getShortOption(string $name): DefinedOption
    {
        return $this->shortOptions[$name];
    }
}
