<?php declare(strict_types=1);

namespace SouthPointe\Cli;

use SouthPointe\Cli\Definitions\ArgumentBuilder;
use SouthPointe\Cli\Definitions\OptionBuilder;
use SouthPointe\Core\Exceptions\LogicException;
use function array_key_exists;
use function array_map;

class CommandBuilder
{
    /**
     * @param string|null $name
     * @param string $description
     * @param array<string, ArgumentBuilder> $argumentBuilders
     * @param array<string, OptionBuilder> $optionBuilders
     * @param array<string, string> $shortNameAliases
     */
    public function __construct(
        protected ?string $name = null,
        protected string $description = '',
        protected array $argumentBuilders = [],
        protected array $optionBuilders = [],
        protected array $shortNameAliases = [],
    )
    {
        $this->addHelpOption();
        $this->addVerboseOption();
    }

    /**
     * @param string $name
     */
    public function name(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string $description
     */
    public function description(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @param string $name
     * @return ArgumentBuilder
     */
    public function argument(string $name): ArgumentBuilder
    {
        if (array_key_exists($name, $this->argumentBuilders)) {
            throw new LogicException("Argument [{$name}] already exists.", [
                'name' => $name,
                'argument' => $this->argumentBuilders[$name],
            ]);
        }
        return $this->argumentBuilders[$name] = new ArgumentBuilder($name);
    }

    /**
     * @param string $name
     * @param string|null $short
     * @return OptionBuilder
     */
    public function option(string $name, ?string $short = null): OptionBuilder
    {
        $builder = new OptionBuilder($name, $short);

        if (array_key_exists($name, $this->optionBuilders)) {
            throw new LogicException("Option [--{$name}] already exists.", [
                'name' => $name,
                'option' => $this->optionBuilders[$name],
            ]);
        }
        $this->optionBuilders[$name] = $builder;

        if ($short !== null) {
            if (array_key_exists($short, $this->shortNameAliases)) {
                throw new LogicException("Option [-{$short}] already exists.", [
                    'name' => $name,
                    'option' => $this->optionBuilders[$name],
                ]);
            }
            $this->shortNameAliases[$short] = $name;
        }

        return $builder;
    }

    public function build(): CommandDefinition
    {
        if ($this->name === null) {
            throw new LogicException('Name of command must be defined!');
        }

        return new CommandDefinition(
            $this->name,
            $this->description,
            array_map(fn($builder) => $builder->build(), $this->argumentBuilders),
            array_map(fn($builder) => $builder->build(), $this->optionBuilders),
            $this->shortNameAliases,
        );
    }

    /**
     * @return $this
     */
    protected function addVerboseOption(): static
    {
        $this->option('verbose', 'v')
            ->description('Set output to verbose mode. Verbosity can be adjusted by calling it multiple times (ex: -vv).')
            ->allowMultiple()
            ->noValue();
        return $this;
    }

    /**
     * @return $this
     */
    protected function addHelpOption(): static
    {
        $this->option('help', 'h')
            ->description('Displays usage and the arguments and options you can use for the command.')
            ->noValue();
        return $this;
    }
}
