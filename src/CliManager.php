<?php declare(strict_types=1);

namespace SouthPointe\Cli;

class CliManager
{
    /**
     * @var array<string, Command>
     */
    protected array $commands = [];

    public function register(Command $command): static
    {
        $name = $command->definition->getName();

        $this->commands[$name] = $command;

        return $this;
    }

    /**
     * @param string $name
     * @param list<string> $parameters
     * @return int
     */
    public function execute(string $name, array $parameters = []): int
    {
        $input = new Input();
        $output = new Output();

        $command = $this->commands[$name];

        return $command->execute($input, $output, $parameters);
    }
}
