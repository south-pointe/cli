<?php declare(strict_types=1);

namespace SouthPointe\Cli\Definitions;

class DefinedArgument extends DefinedParameter
{
    /**
     * @param string $name
     * @param string $description
     * @param bool $multiple
     * @param bool $optional
     * @param string|list<string>|null $default
     */
    public function __construct(
        string $name,
        string $description = '',
        bool $multiple = false,
        protected readonly bool $optional = false,
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
     * @return bool
     */
    public function isOptional(): bool
    {
        return $this->optional;
    }
}
