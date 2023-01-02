<?php declare(strict_types=1);

namespace SouthPointe\Cli\Definitions;

class ArgumentBuilder extends ParameterBuilder
{
    /**
     * @var bool
     */
    protected bool $optional = false;

    /**
     * @return DefinedArgument
     */
    public function build(): DefinedArgument
    {
        return new DefinedArgument(
            $this->name,
            $this->description,
            $this->multiple,
            $this->optional,
            $this->default,
        );
    }

    /**
     * @param string|null $default
     * @return $this
     */
    public function optional(string $default = null): static
    {
        $this->optional = true;
        $this->default = $default;
        return $this;
    }
}
