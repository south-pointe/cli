<?php declare(strict_types=1);

namespace SouthPointe\Cli\Definitions;

class DefinedOption extends DefinedParameter
{
    public function __construct(
        string $name,
        protected ?string $short = null,
        string $description = '',
        protected bool $requireValue = true,
        bool $multiple = false,
        bool $optional = false,
        ?string $default = null,
    )
    {
        parent::__construct(
            $name,
            $description,
            $multiple,
            $optional,
            $default,
        );
    }

    /**
     * @return string|null
     */
    public function getShortName(): ?string
    {
        return $this->short;
    }

    /**
     * @return bool
     */
    public function requireValue(): bool
    {
        return $this->requireValue;
    }
}
