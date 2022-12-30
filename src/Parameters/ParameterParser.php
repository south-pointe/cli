<?php declare(strict_types=1);

namespace SouthPointe\Cli\Parameters;

use SouthPointe\Cli\CommandDefinition;
use SouthPointe\Cli\Definitions\DefinedArgument;
use SouthPointe\Cli\Definitions\DefinedOption;
use SouthPointe\Cli\Exceptions\ParseException;
use SouthPointe\Core\Exceptions\LogicException;
use SouthPointe\Core\Exceptions\RuntimeException;
use function array_key_exists;
use function array_slice;
use function count;
use function explode;
use function is_array;
use function is_null;
use function is_string;
use function preg_match;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;

class ParameterParser
{
    /**
     * @var int
     */
    protected int $parameterCursor = 0;

    /**
     * @var int
     */
    protected int $argumentCursor = 0;

    /**
     * @var array<string, Argument>
     */
    protected array $parsedArguments = [];

    /**
     * @var array<string, Option>
     */
    protected array $parsedOptions = [];

    /**
     * @param CommandDefinition $definition
     * @param list<string> $parameters
     */
    public function __construct(
        protected CommandDefinition $definition,
        protected array $parameters,
    )
    {
    }

    /**
     * @return array{
     *     arguments: array<string, Argument>,
     *     options: array<string, Option>,
     * }
     */
    public function parse(): array
    {
        $parameterCount = count($this->parameters);

        while ($this->parameterCursor < $parameterCount) {
            $parameter = $this->parameters[$this->parameterCursor];
            match (true) {
                $this->isLongOption($parameter) => $this->processLongOption($parameter),
                $this->isShortOption($parameter) => $this->processShortOptions($parameter),
                default => $this->processArgument($parameter),
            };
        }

        $this->addRemainingArguments();

        return [
            'arguments' => $this->parsedArguments,
            'options' => $this->parsedOptions,
        ];
    }

    /**
     * @return bool
     */
    protected function hasMoreParameters(): bool
    {
        return $this->parameterCursor < count($this->parameters);
    }

    /**
     * @return string|null
     */
    protected function nextParameter(): ?string
    {
        return $this->parameters[$this->parameterCursor + 1] ?? null;
    }

    /**
     * @param string $parameter
     * @return bool
     */
    protected function isLongOption(string $parameter): bool
    {
        return (bool)preg_match('/--\w+/', $parameter);
    }

    /**
     * @param string $parameter
     * @return bool
     */
    protected function isShortOption(string $parameter): bool
    {
        return (bool)preg_match('/-\w+/', $parameter);
    }

    /**
     * @param string $parameter
     * @return bool
     */
    protected function isNotAnOption(string $parameter): bool
    {
        return !str_starts_with($parameter, '-');
    }

    /**
     * @param string $parameter
     * @return void
     */
    protected function processLongOption(string $parameter): void
    {
        $parts = explode('=', substr($parameter, 2));
        $name = $parts[0];
        $value = $parts[1] ?? null;

        $defined = $this->getDefinedLongOption($name);

        if ($defined === null) {
            throw new ParseException("Undefined option: {$name}", [
                'parameters' => $this->parameters,
                'parameter' => $parameter,
            ]);
        }

        if ($value === null && $this->hasMoreParameters()) {
            // look at the next parameter to check if it's a value
            $nextParameter = $this->nextParameter();
            if ($nextParameter !== null && $this->isNotAnOption($nextParameter)) {
                $value = $nextParameter;
                $this->parameterCursor++;
            }
        }

        $value ??= $defined->getDefault();

        if ($defined->requireValue()) {
            throw new ParseException("Value is required for option: {$name}", [
                'option' => $defined,
                'parameter' => $parameter,
            ]);
        }

        $this->addAsOption($defined, $name, $value);
    }

    /**
     * @param string $parameter
     * @return void
     */
    protected function processShortOptions(string $parameter): void
    {
        $chars = substr($parameter, 1);

        for ($i = 0, $size = strlen($chars); $i < $size; $i++) {
            $char = $chars[$i];
            $defined = $this->getDefinedShortOption($char);

            if ($defined === null) {
                throw new RuntimeException(sprintf('Undefined option: %s', $char));
            }

            $nextChar = $chars[$i + 1] ?? false;

            // on the last char, no need to go further.
            if ($nextChar === false) {
                if ($this->hasMoreParameters()) {
                    $nextParameter = $this->nextParameter();
                    if($nextParameter !== null && $this->isNotAnOption($nextParameter)) {
                        $this->addAsOption($defined, $char, $nextParameter);
                        $this->parameterCursor++;
                    } else {
                        $this->addAsOption($defined, $char, null);
                    }
                }
                break;
            }

            // if next char is another option, add the current option and move on.
            if ($this->definition->shortOptionExists($nextChar)) {
                $this->addAsOption($defined, $char, $defined->getDefault());
                continue;
            }

            // if next char is not an option, assume it's an argument.
            $remainingChars = substr($chars, $i);
            if ($defined->requireValue()) {
                $this->addAsOption($defined, $char, $remainingChars);
                break;
            }

            throw new RuntimeException(sprintf('option: "-%s" does not accept values: "%s"', $char, $remainingChars));
        }
    }

    /**
     * @param string $parameter
     * @return void
     */
    protected function processArgument(string $parameter): void
    {
        $defined = $this->definition->getArgumentByIndex($this->argumentCursor);

        if ($defined === null) {
            throw new ParseException("Invalid Argument: \"{$parameter}\" at [{$this->argumentCursor}]", [
                'parameters' => $this->parameters,
                'cursor' => $this->argumentCursor,
            ]);
        }

        $this->addAsArgument($defined, $parameter);

        if (!$defined->isArray()) {
            $this->argumentCursor++;
        }

        $this->parameterCursor++;
    }

    /**
     * @return void
     */
    protected function addRemainingArguments(): void
    {
        $allArguments = $this->definition->getArguments();
        $remainingArguments = array_slice($allArguments, $this->argumentCursor, null, true);
        foreach ($remainingArguments as $name => $argument) {
            if ($argument->isArray() && isset($this->parsedArguments[$name])) {
                continue;
            }

            if (!$argument->isOptional()) {
                throw new ParseException("Missing required argument: {$name}", [
                    'parameters' => $this->parameters,
                    'argument' => $argument,
                ]);
            }

            if ($argument->hasDefault()) {
                $this->addAsDefaultArgument($argument);
            }
        }
    }

    /**
     * @param string $name
     * @return DefinedOption|null
     */
    protected function getDefinedLongOption(string $name): ?DefinedOption
    {
        return $this->checkOptionCount($this->definition->getLongOption($name));
    }

    /**
     * @param string $name
     * @return DefinedOption|null
     */
    protected function getDefinedShortOption(string $name): ?DefinedOption
    {
        return $this->checkOptionCount($this->definition->getShortOption($name));
    }

    /**
     * @param DefinedOption $option
     * @return DefinedOption
     */
    protected function checkOptionCount(DefinedOption $option): DefinedOption
    {
        $longName = $option->getName();

        if (array_key_exists($longName, $this->parsedOptions) && !$option->isArray()) {
            throw new ParseException("Option: {$longName} cannot be entered more than once", [
                'option' => $option,
                'parameters' => $this->parameters,
            ]);
        }

        return $option;
    }

    /**
     * @param DefinedOption $defined
     * @param string $enteredName
     * @param mixed $enteredValue
     * @return void
     */
    protected function addAsOption(
        DefinedOption $defined,
        string $enteredName,
        mixed $enteredValue
    ): void
    {
        $longName = $defined->getName();
        $this->parsedOptions[$longName] ??= new Option($defined, $enteredName);
        $this->parsedOptions[$longName]->addValue($enteredValue);
    }

    /**
     * @param DefinedArgument $argument
     * @param string $enteredValue
     * @return void
     */
    protected function addAsArgument(
        DefinedArgument $argument,
        string $enteredValue,
    ): void
    {
        $name = $argument->getName();
        $this->parsedArguments[$name] ??= new Argument($argument, true);
        $this->parsedArguments[$name]->addValue($enteredValue);
    }

    /**
     * @param DefinedArgument $argument
     * @return void
     */
    protected function addAsDefaultArgument(DefinedArgument $argument): void
    {
        $name = $argument->getName();
        $this->parsedArguments[$name] = new Argument($argument, false);
        $default = $argument->getDefault();
        if ($argument->isArray()) {
            if (!is_array($default)) {
                throw new LogicException("Argument: {$name}'s default value must be an array, since it's a multi argument.", [
                    'argument' => $argument,
                    'default' => $default,
                ]);
            }
            foreach ($default as $value) {
                $this->parsedArguments[$name]->addValue($value);
            }
        } else {
            if (!is_string($default) && !is_null($default)) {
                throw new LogicException("Argument: {$name}'s default value must be defined as string.", [
                    'argument' => $argument,
                    'default' => $default,
                ]);
            }
            $this->parsedArguments[$name]->addValue($default);
        }
    }
}
