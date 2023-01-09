<?php declare(strict_types=1);

namespace SouthPointe\Cli\Parameters;

use SouthPointe\Cli\Definitions\ParameterDefinition;
use SouthPointe\Core\Exceptions\RuntimeException;
use function array_key_exists;

/**
 * @template TDefined as ParameterDefinition
 */
abstract class Parameter
{
    /**
     * @param TDefined $defined
     * @param list<string> $values
     */
    public function __construct(
        public readonly ParameterDefinition $defined,
        public readonly bool $wasEntered,
        public readonly array $values = [],
    )
    {
    }

    /**
     * @param int $index
     * @return string
     */
    public function getValue(int $index = 0): string
    {
        $values = $this->values;

        if (!array_key_exists($index, $values)) {
            throw new RuntimeException("No values exists at [{$index}]", [
                'at' => $index,
                'values' => $values,
            ]);
        }

        return $values[$index];
    }
}
