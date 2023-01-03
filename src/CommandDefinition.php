<?php declare(strict_types=1);

namespace SouthPointe\Cli;

use SouthPointe\Cli\Definitions\ArgumentDefinition;
use SouthPointe\Cli\Definitions\OptionDefinition;
use function array_key_exists;
use function array_values;

class CommandDefinition
{
    /**
     * @var array<int, ArgumentDefinition>
     */
    protected array $argumentsByIndex;

    /**
     * @var array<string, ArgumentDefinition>
     */
    protected array $argumentsByName;

    /**
     * @param string $name
     * @param string|null $description
     * @param array<string, ArgumentDefinition> $arguments
     * @param array<string, OptionDefinition> $longOptions
     * @param array<string, OptionDefinition> $shortOptions
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
     * @return array<string, ArgumentDefinition>
     */
    public function getArguments(): array
    {
        return $this->argumentsByName;
    }

    /**
     * @param int $index
     * @return ArgumentDefinition|null
     */
    public function getArgumentByIndexOrNull(int $index): ?ArgumentDefinition
    {
        return $this->argumentsByIndex[$index] ?? null;
    }

    /**
     * @param string $name
     * @return ArgumentDefinition
     */
    public function getArgumentByName(string $name): ArgumentDefinition
    {
        return $this->argumentsByName[$name];
    }

    /**
     * @return array<string, OptionDefinition>
     */
    public function getLongOptions(): array
    {
        return $this->longOptions;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function longOptionExists(string $name): bool
    {
        return array_key_exists($name, $this->longOptions);
    }

    /**
     * @param string $name
     * @return OptionDefinition
     */
    public function getLongOption(string $name): OptionDefinition
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
     * @return OptionDefinition
     */
    public function getShortOption(string $name): OptionDefinition
    {
        return $this->shortOptions[$name];
    }
}
