<?php declare(strict_types=1);

namespace SouthPointe\Cli\Definitions;

class OptionBuilder extends ParameterBuilder
{
    /**
     * @param string $name
     * @param string|null $short
     * @param bool $valueRequired
     */
    public function __construct(
        string $name,
        protected ?string $short = null,
        protected bool $valueRequired = false,
    )
    {
        parent::__construct($name);
    }

    /**
     * @return OptionDefinition
     */
    public function build(): OptionDefinition
    {
        return new OptionDefinition(
            $this->name,
            $this->short,
            $this->description,
            $this->valueRequired,
            $this->allowMultiple,
            $this->default,
        );
    }

    /**
     * @param string|list<string>|null $default
     * @return $this
     */
    public function requiresValue(string|array|null $default = null): static
    {
        $this->valueRequired = true;
        $this->default = $default;
        return $this;
    }

    /**
     * @return $this
     */
    public function noValue(): static
    {
        $this->valueRequired = false;
        $this->default = null;
        return $this;
    }
}
