<?php declare(strict_types=1);

namespace SouthPointe\Cli\Definitions;

class OptionBuilder extends ParameterBuilder
{
    /**
     * @var bool
     */
    protected bool $valueRequired = false;

    /**
     * @param string $name
     * @param string|null $short
     */
    public function __construct(
        string $name,
        protected ?string $short = null,
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
