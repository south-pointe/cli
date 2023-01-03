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
     * @param list<string|null>|null $values
     */
    public function __construct(
        protected readonly ParameterDefinition $defined,
        protected readonly bool $wasEntered,
        protected readonly ?array $values = null,
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
     * @param int $at
     * @return string|null
     */
    public function getValue(int $at = 0): ?string
    {
        $values = $this->getValues();

        if (!array_key_exists($at, $values)) {
            throw new RuntimeException("No values exists at [{$at}]", [
                'at' => $at,
                'values' => $values,
            ]);
        }

        return $values[$at];
    }

    /**
     * @return list<string|null>
     */
    public function getValues(): array
    {
        return $this->values ?? [];
    }
}
