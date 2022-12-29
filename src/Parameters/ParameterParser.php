<?php declare(strict_types=1);

namespace SouthPointe\Cli\Parameters;

use RuntimeException;
use SouthPointe\Cli\CommandDefinition;
use SouthPointe\Cli\Definitions\DefinedArgument;
use SouthPointe\Cli\Definitions\DefinedOption;
use SouthPointe\Cli\Exceptions\ParseException;
use function array_key_exists;
use function array_slice;
use function count;
use function explode;
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
    protected array $enteredArguments = [];

    /**
     * @var array<string, Option>
     */
    protected array $enteredOptions = [];

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

        $this->checkRemainingArguments();

        return [
            'arguments' => $this->enteredArguments,
            'options' => $this->enteredOptions,
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
            throw new RuntimeException(sprintf('Undefined option: %s', $name));
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
            throw new RuntimeException(sprintf('Value is required for option: %s', $name));
        }

        $this->addToOption($defined, $name, $value);
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
                        $this->addToOption($defined, $char, $nextParameter);
                        $this->parameterCursor++;
                    } else {
                        $this->addToOption($defined, $char, null);
                    }
                }
                break;
            }

            // if next char is another option, add the current option and move on.
            if ($this->definition->shortOptionExists($nextChar)) {
                $this->addToOption($defined, $char, $defined->getDefault());
                continue;
            }

            // if next char is not an option, assume it's an argument.
            $remainingChars = substr($chars, $i);
            if ($defined->requireValue()) {
                $this->addToOption($defined, $char, $remainingChars);
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

        $this->addToArgument($defined, $parameter);

        if (!$defined->isArray()) {
            $this->argumentCursor++;
        }

        $this->parameterCursor++;
    }

    /**
     * @return void
     */
    protected function checkRemainingArguments(): void
    {
        $allArguments = $this->definition->getArguments();
        $remainingArguments = array_slice($allArguments, $this->argumentCursor);
        foreach ($remainingArguments as $argument) {
            if ($argument->isArray() && isset($this->enteredArguments[$argument->getName()])) {
                continue;
            }

            if (!$argument->isOptional()) {
                throw new ParseException("Missing argument: " . $argument->getName(), [
                    'parameters' => $this->parameters,
                    'argument' => $argument,
                ]);
            }

            $default = $argument->getDefault();
            if ($default !== null) {
                $this->addToArgument($argument, $default);
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

        if (array_key_exists($longName, $this->enteredOptions) && !$option->isArray()) {
            throw new RuntimeException(sprintf('Option: %s cannot be entered more than once', $longName));
        }

        return $option;
    }

    /**
     * @param DefinedOption $defined
     * @param string $entered
     * @param mixed $value
     * @return Option
     */
    protected function addToOption(DefinedOption $defined, string $entered, mixed $value): Option
    {
        $longName = $defined->getName();

        $this->enteredOptions[$longName] ??= new Option($defined, $entered);
        $this->enteredOptions[$longName]->addValue($value);

        return $this->enteredOptions[$longName];
    }

    /**
     * @param DefinedArgument $argument
     * @param mixed $value
     * @return Argument
     */
    protected function addToArgument(DefinedArgument $argument, mixed $value): Argument
    {
        $name = $argument->getName();

        $this->enteredArguments[$name] ??= new Argument($argument);
        $this->enteredArguments[$name]->addValue($value);

        return $this->enteredArguments[$name];
    }
}
