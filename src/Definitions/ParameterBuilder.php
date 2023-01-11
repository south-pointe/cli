<?php declare(strict_types=1);

namespace SouthPointe\Cli\Definitions;

class ParameterBuilder
{
    /**
     * @param string $name
     * @param string $description
     * @param bool $allowMultiple
     * @param string|list<string>|null $default
     */
    public function __construct(
        protected string $name,
        protected string $description = '',
        protected bool $allowMultiple = false,
        protected string|array|null $default = null,
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
