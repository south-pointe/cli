<?php declare(strict_types=1);

namespace SouthPointe\Cli;

use SouthPointe\Cli\Parameters\ParameterParser;
use Webmozart\Assert\Assert;

abstract class Command
{
    /**
     * @var CommandDefinition
     */
    protected readonly CommandDefinition $definition;

    /**
     * @var Parameters
     */
    protected Parameters $parameters;

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
     * @param list<string> $rawParameters
     * @return int
     */
    public function execute(Input $input, Output $output, array $rawParameters): int
    {
        $this->input = $input;

        $this->output = $output;

        $parser = new ParameterParser($this->definition, $rawParameters);
        $parsed = $parser->parse();

        $this->parameters = new Parameters(
            $parsed['arguments'],
            $parsed['options'],
        );

        $code = $this->run() ?? 0;

        Assert::range($code, 0, 255);

        return $code;
    }

    /**
     * The method which runs the user defined logic.
     *
     * @return int|null
     */
    abstract public function run(): ?int;
}
