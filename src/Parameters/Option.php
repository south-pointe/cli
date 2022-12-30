<?php declare(strict_types=1);

namespace SouthPointe\Cli\Parameters;

use SouthPointe\Cli\Definitions\DefinedOption;

/**
 * @extends Parameter<DefinedOption>
 */
class Option extends Parameter
{
    /**
     * @param DefinedOption $defined
     * @param string|null $enteredName
     */
    public function __construct(
        DefinedOption $defined,
        protected readonly ?string $enteredName = null,
    )
    {
        parent::__construct($defined, $this->enteredName !== null);
    }

    /**
     * @return string|null
     */
    public function getEnteredName(): ?string
    {
        return $this->enteredName;
    }
}
