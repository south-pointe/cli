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
        protected readonly ParameterDefinition $defined,
        protected readonly bool $wasEntered,
        protected readonly array $values = [],
    )
    {
    }

    /**
     * @return TDefined
     */
    public function getDefinition(): ParameterDefinition
    {
        return $this->defined;
    }

    /**
     * @return bool
     */
    public function wasEntered(): bool
    {
        return $this->wasEntered;
    }

    /**
     * @param int $index
     * @return string
     */
    public function getValue(int $index = 0): string
    {
        $values = $this->getValues();

        if (!array_key_exists($index, $values)) {
            throw new RuntimeException("No values exists at [{$index}]", [
                'at' => $index,
                'values' => $values,
            ]);
        }

        return $values[$index];
    }

    /**
     * @return list<string>
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
