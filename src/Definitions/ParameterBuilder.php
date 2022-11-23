<?php declare(strict_types=1);

namespace SouthPointe\Cli\Definitions;

/**
 * @template TParameter of Parameter
 */
class ParameterBuilder
{
    /**
     * @var bool
     */
    protected bool $multiple = false;

    /**
     * @var string
     */
    protected string $description = '';

    /**
     * @var bool
     */
    protected bool $optional = false;

    /**
     * @var string|null
     */
    protected ?string $default = null;

    public function __construct(
        protected string $name
    )
    {
    }

    /**
     * @param bool $toggle
     * @return $this
     */
    public function allowMultiple(bool $toggle = true): static
    {
        $this->multiple = $toggle;
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function description(string $text): static
    {
        $this->description = $text;
        return $this;
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
