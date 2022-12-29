<?php declare(strict_types=1);

namespace SouthPointe\Cli\Parameters;

use ReflectionClass;
use RuntimeException;
use SouthPointe\Cli\Definitions\DefinedParameter;
use function count;
use function sprintf;

/**
 * @template TDefined as DefinedParameter
 */
abstract class Parameter
{
    /**
     * @var list<string|null>
     */
    protected array $values = [];

    /**
     * @param TDefined $defined
     */
    public function __construct(
        protected readonly DefinedParameter $defined,
    )
    {
    }

    /**
     * @param string|null $value
     * @return void
     */
    public function addValue(?string $value): void
    {
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
     * @return list<string|null>
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
