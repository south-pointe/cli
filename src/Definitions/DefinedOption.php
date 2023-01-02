<?php declare(strict_types=1);

namespace SouthPointe\Cli\Definitions;

class DefinedOption extends DefinedParameter
{
    /**
     * @param string $name
     * @param string|null $short
     * @param string $description
     * @param bool $valueRequired
     * @param bool $multiple
     * @param string|list<string>|null $default
     */
    public function __construct(
        string $name,
        protected ?string $short = null,
        string $description = '',
        protected bool $valueRequired = true,
        bool $multiple = false,
        string|array|null $default = null,
    )
    {
        parent::__construct(
            $name,
            $description,
            $multiple,
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
    public function valueRequired(): bool
    {
        return $this->valueRequired;
    }
}
