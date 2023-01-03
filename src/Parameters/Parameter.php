<?php declare(strict_types=1);

namespace SouthPointe\Cli\Parameters;

use ReflectionClass;
use SouthPointe\Cli\Definitions\DefinedParameter;
use SouthPointe\Core\Exceptions\RuntimeException;
use function array_key_exists;
use function count;
use function sprintf;

/**
 * @template TDefined as DefinedParameter
 */
abstract class Parameter
{
    /**
     * @param TDefined $defined
     * @param list<string|null>|null $values
     */
    public function __construct(
        protected readonly DefinedParameter $defined,
        protected readonly bool $wasEntered,
        protected ?array $values = null,
    )
    {
    }

    /**
     * @param string|null $value
     * @return void
     */
    public function addValue(?string $value): void
    {
        $this->values ??= [];

        if (count($this->values) > 0 && !$this->defined->isArray()) {
            throw new RuntimeException(
                sprintf(
                    '%s: %s does not accept array of inputs',
                    (new ReflectionClass($this))->getShortName(),
                    $this->defined->getName(),
                )
            );
        }

        $this->values[] = $value;
    }

    /**
     * @return TDefined
     */
    public function getDefinition(): DefinedParameter
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
