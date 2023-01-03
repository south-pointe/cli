<?php declare(strict_types=1);

namespace SouthPointe\Cli\Definitions;

class ParameterBuilder
{
    /**
     * @var string
     */
    protected string $description = '';

    /**
     * @var bool
     */
    protected bool $allowMultiple = false;

    /**
     * @var string|list<string>|null
     */
    protected string|array|null $default = null;

    /**
     * @param string $name
     */
    public function __construct(
        protected string $name,
    )
    {
    }

    /**
     * @param bool $toggle
     * @return $this
     */
    public function allowMultiple(bool $toggle = true): static
    {
        $this->allowMultiple = $toggle;
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
}
