<?php declare(strict_types=1);

namespace SouthPointe\Cli\Definitions;

abstract class ParameterDefinition
{
    /**
     * @param string $name
     * @param string $description
     * @param bool $allowMultiple
     * @param string|list<string>|null $default
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description = '',
        public readonly bool $allowMultiple = false,
        public readonly string|array|null $default = null,
    )
    {
    }
}
