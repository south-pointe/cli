<?php declare(strict_types=1);

namespace SouthPointe\Cli\Parameters;

use SouthPointe\Cli\Definitions\DefinedArgument;

/**
 * @template-extends Parameter<DefinedArgument>
 */
class Argument extends Parameter
{
    /**
     * @param DefinedArgument $defined
     */
    public function __construct(
        DefinedArgument $defined,
    )
    {
        parent::__construct($defined);
    }
}
