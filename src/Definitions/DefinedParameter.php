<?php declare(strict_types=1);

namespace SouthPointe\Cli\Definitions;

abstract class DefinedParameter
{
    /**
     * @param string $name
     * @param string $description
     * @param bool $multiple
     * @param bool $optional
     * @param string|null $default
     */
    public function __construct(
        protected readonly string $name,
        protected readonly string $description = '',
        protected readonly bool $multiple = false,
        protected readonly bool $optional = false,
        protected readonly ?string $default = null,
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
    public function isOptional(): bool
    {
        return $this->optional;
    }

    /**
     * @return string|null
     */
    public function getDefault(): ?string
    {
        return $this->default;
    }
}
