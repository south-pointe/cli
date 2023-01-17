<?php declare(strict_types=1);

namespace SouthPointe\Cli\Definitions;

class OptionDefinition extends ParameterDefinition
{
    /**
     * @param string $name
     * @param string|null $short
     * @param string $description
     * @param bool $valueRequired
     * @param bool $allowMultiple
     * @param string|list<string>|null $default
     */
    public function __construct(
        string $name,
        public ?string $short = null,
        string $description = '',
        public bool $valueRequired = false,
        bool $allowMultiple = false,
        string|array|null $default = null,
    )
    {
        parent::__construct(
            $name,
            $description,
            $allowMultiple,
            $default,
        );
    }
}
