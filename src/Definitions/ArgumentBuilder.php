<?php declare(strict_types=1);

namespace SouthPointe\Cli\Definitions;

class ArgumentBuilder extends ParameterBuilder
{
    /**
     * @var bool
     */
    protected bool $optional = false;

    /**
     * @return ArgumentDefinition
     */
    public function build(): ArgumentDefinition
    {
        return new ArgumentDefinition(
            $this->name,
            $this->description,
            $this->allowMultiple,
            $this->optional,
            $this->default,
        );
    }

    /**
     * @param string|list<string>|null $default
     * @return $this
     */
    public function optional(string|array|null $default = null): static
    {
        $this->optional = true;
        $this->default = $default;
        return $this;
    }
}
