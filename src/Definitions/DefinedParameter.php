<?php declare(strict_types=1);

namespace SouthPointe\Cli\Definitions;

abstract class DefinedParameter
{
    /**
     * @param string $name
     * @param string $description
     * @param bool $multiple
     * @param string|list<string>|null $default
     */
    public function __construct(
        protected readonly string $name,
        protected readonly string $description = '',
        protected readonly bool $multiple = false,
        protected readonly string|array|null $default = null,
    )
    {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function isArray(): bool
    {
        return $this->multiple;
    }

    /**
     * @return bool
     */
    public function hasDefault(): bool
    {
        return $this->default !== null;
    }

    /**
     * @return string|list<string>|null
     */
    public function getDefault(): string|array|null
    {
        return $this->default;
    }
}
