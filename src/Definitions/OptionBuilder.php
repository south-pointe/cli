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
     * @return DefinedOption
     */
    public function build(): DefinedOption
    {
        return new DefinedOption(
            $this->name,
            $this->short,
            $this->description,
            $this->valueRequired,
            $this->multiple,
            $this->default,
        );
    }

    /**
     * @param string|list<string>|null $default
     * @return $this
     */
    public function requireValue(string|array|null $default = null): static
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
