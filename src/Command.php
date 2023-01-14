<?php declare(strict_types=1);

namespace SouthPointe\Cli;

use SouthPointe\Cli\Parameters\Argument;
use SouthPointe\Cli\Parameters\Option;
use SouthPointe\Cli\Parameters\ParameterParser;
use SouthPointe\Core\Exceptions\LogicException;

abstract class Command
{
    /**
     * @var CommandDefinition
     */
    public readonly CommandDefinition $definition;

    /**
     * @var array<string, Argument>
     */
    protected array $arguments;

    /**
     * @var array<string, Option>
     */
    protected array $options;

    /**
     * @var Input
     */
    protected Input $input;

    /**
     * @var Output
     */
    protected Output $output;

    public function __construct()
    {
        $builder = new CommandBuilder();
        $this->define($builder);
        $this->definition = $builder->build();
    }

    /**
     * Define the command and its arguments and options.
     *
     * @param CommandBuilder $builder
     * @return void
     */
    abstract protected function define(CommandBuilder $builder): void;

    /**
     * Parse the raw parameters and run the command.
     *
     * @param Input $input
     * @param Output $output
     * @param list<string> $parameters
     * @return int
     */
    public function execute(Input $input, Output $output, array $parameters): int
    {
        $parsed = $this->parseDefinition($parameters);

        $this->arguments = $parsed['arguments'];
        $this->options = $parsed['options'];
        $this->input = $input;
        $this->output = $output;

        $code = $this->run() ?? 0;

        if ($code < 0 || $code > 255) {
            throw new LogicException("Exit code must be between 0 and 255, {$code} given.", [
                'code' => $code,
            ]);
        }

        return $code;
    }

    /**
     * The method which runs the user defined logic.
     *
     * @return int|null
     * Exit code for the given command.
     * Must be between 0 and 255.
     */
    abstract public function run(): ?int;

    /**
     * @param list<string> $parameters
     * @return array{
     *     arguments: array<string, Argument>,
     *     options: array<string, Option>,
     * }
     */
    protected function parseDefinition(array $parameters): array
    {
        return ParameterParser::parse($this->definition, $parameters);
    }
}
