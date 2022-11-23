<?php declare(strict_types=1);

namespace SouthPointe\Cli;

class CliHandler
{
    /**
     * @var array<int, Command>
     */
    protected array $commands = [];

    public function register(Command $command): static
    {
        $this->commands[] = $command;
        return $this;
    }
}
