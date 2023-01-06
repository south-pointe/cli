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
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * @var string|null
     */
    protected ?string $description = null;

    /**
     * @var array<string, ArgumentBuilder>
     */
    protected array $argumentBuilders = [];

    /**
     * @var array<string, OptionBuilder>
     */
    protected array $longOptionBuilders = [];

    /**
     * @var array<string, OptionBuilder>
     */
    protected array $shortOptionBuilders = [];

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

        if (array_key_exists($name, $this->longOptionBuilders)) {
            throw new LogicException("Option [--{$name}] already exists.", [
                'name' => $name,
                'option' => $this->longOptionBuilders[$name],
            ]);
        }
        $this->longOptionBuilders[$name] = $builder;

        if ($short !== null) {
            if (array_key_exists($short, $this->shortOptionBuilders)) {
                throw new LogicException("Option [-{$short}] already exists.", [
                    'name' => $name,
                    'option' => $this->shortOptionBuilders[$short],
                ]);
            }
            $this->shortOptionBuilders[$short] = $builder;
        }

        return $builder;
    }

    public function build(): CommandDefinition
    {
        if ($this->name === null) {
            throw new LogicException('Name of command must be defined!');
        }

        $arguments = array_map(
            fn(ArgumentBuilder $builder) => $builder->build(),
            $this->argumentBuilders
        );

        $longOptions = array_map(
            fn(OptionBuilder $builder) => $builder->build(),
            $this->longOptionBuilders
        );

        $shortOptions = array_map(
            fn(OptionBuilder $builder) => $builder->build(),
            $this->shortOptionBuilders
        );

        return new CommandDefinition(
            $this->name,
            $this->description,
            $arguments,
            $longOptions,
            $shortOptions,
        );
    }
}
