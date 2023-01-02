<?php declare(strict_types=1);

namespace SouthPointe\Cli\Definitions;

class OptionBuilder extends ParameterBuilder
{
    /**
     * @var bool
     */
    protected bool $requireValue = false;

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
     * @param bool $toggle
     * @return $this
     */
    public function requireValue(bool $toggle = true): static
    {
        $this->requireValue = $toggle;
        return $this;
    }

    /**
     * @param string|list<string>|null $value
     * @return $this
     */
    public function default(string|array|null $value): static
    {
        $this->default = $value;
        return $this;
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
            $this->requireValue,
            $this->multiple,
            $this->default,
        );
    }
}
