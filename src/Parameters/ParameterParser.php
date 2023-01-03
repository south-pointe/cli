<?php declare(strict_types=1);

namespace SouthPointe\Cli\Parameters;

use SouthPointe\Cli\CommandDefinition;
use SouthPointe\Cli\Definitions\DefinedArgument;
use SouthPointe\Cli\Definitions\DefinedOption;
use SouthPointe\Cli\Exceptions\ParseException;
use function array_diff_key;
use function array_key_exists;
use function array_slice;
use function count;
use function explode;
use function is_array;
use function is_null;
use function is_string;
use function preg_match;
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
        $this->addRemainingOptions();

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
    protected function nextParameterOrNull(): ?string
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
            throw new ParseException("Option: [--{$name}] is not defined.", [
                'parameters' => $this->parameters,
                'parameter' => $parameter,
            ]);
        }

        if ($value === null && $this->hasMoreParameters()) {
            // look at the next parameter to check if it's a value
            $nextParameter = $this->nextParameterOrNull();
            if ($nextParameter !== null && $this->isNotAnOption($nextParameter)) {
                $value = $nextParameter;
                $this->parameterCursor++;
            }
        }

        $value ??= $defined->getDefault();

        if ($value === null && $defined->valueRequired()) {
            throw new ParseException("Option: [--{$name}] requires a value.", [
                'option' => $defined,
                'parameter' => $parameter,
            ]);
        }

        $this->addAsOption($defined, $value);

        $this->parameterCursor++;
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
                throw new ParseException("Option: [-{$char}] is not defined.", [
                    'parameters' => $this->parameters,
                    'cursor' => $this->parameterCursor,
                    'char' => $char,
                ]);
            }

            $nextChar = $chars[$i + 1] ?? false;

            // on the last char, no need to go further.
            if ($nextChar === false) {
                $nextParameter = $this->nextParameterOrNull();
                if ($nextParameter !== null && $this->isNotAnOption($nextParameter)) {
                    $this->addAsOption($defined, $nextParameter);
                    $this->parameterCursor+= 2;
                    break;
                }

                $default = $defined->getDefault();

                if ($default === null && $defined->valueRequired()) {
                    throw new ParseException("Option: [-{$char}] requires a value.", [
                        'option' => $defined,
                        'parameter' => $parameter,
                    ]);
                }

                $this->addAsOption($defined, $default);
                $this->parameterCursor++;
                break;
            }

            // if next char is another option, add the current option and move on.
            if ($this->definition->shortOptionExists($nextChar)) {
                $this->addAsOption($defined, $defined->getDefault());
                $this->parameterCursor++;
                continue;
            }

            // if next char is not an option, assume it's an argument.
            $remainingChars = substr($chars, $i + 1);
            if ($defined->valueRequired()) {
                $this->addAsOption($defined, $remainingChars);
                $this->parameterCursor++;
                break;
            }

            throw new ParseException("[option: -{$char} (--{$defined->getName()})] invalid value: \"{$remainingChars}\"", [
                'option' => $defined,
                'parameters' => $this->parameters,
                'cursor' => $this->parameterCursor,
                'char' => $char,
            ]);
        }
    }

    /**
     * @param string $parameter
     * @return void
     */
    protected function processArgument(string $parameter): void
    {
        $defined = $this->definition->getArgumentByIndexOrNull($this->argumentCursor);

        if ($defined === null) {
            throw new ParseException("Argument [{$this->argumentCursor}: \"{$parameter}\"] is not defined.", [
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
        $all = $this->definition->getArguments();
        $remaining = array_slice($all, $this->argumentCursor, null, true);
        foreach ($remaining as $name => $argument) {
            if ($argument->isArray() && isset($this->parsedArguments[$name])) {
                continue;
            }

            if (!$argument->isOptional()) {
                throw new ParseException("Missing required argument: {$name}", [
                    'parameters' => $this->parameters,
                    'argument' => $argument,
                ]);
            }

            $this->addAsDefaultArgument($argument);
        }
    }

    /**
     * @return void
     */
    protected function addRemainingOptions(): void
    {
        $all = $this->definition->getLongOptions();
        $remaining = array_diff_key($all, $this->parsedOptions);
        foreach ($remaining as $defined) {
            $this->addAsDefaultOption($defined);
        }
    }

    /**
     * @param string $name
     * @return DefinedOption|null
     */
    protected function getDefinedLongOption(string $name): ?DefinedOption
    {
        return $this->definition->longOptionExists($name)
            ? $this->checkOptionCount($this->definition->getLongOption($name))
            : null;
    }

    /**
     * @param string $name
     * @return DefinedOption|null
     */
    protected function getDefinedShortOption(string $name): ?DefinedOption
    {
        return $this->definition->shortOptionExists($name)
            ? $this->checkOptionCount($this->definition->getShortOption($name))
            : null;
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
     * @param DefinedArgument $argument
     * @param string $value
     * @return void
     */
    protected function addAsArgument(
        DefinedArgument $argument,
        string $value,
    ): void
    {
        $name = $argument->getName();
        $this->parsedArguments[$name] ??= new Argument($argument, true);
        $this->parsedArguments[$name]->addValue($value);
    }

    /**
     * @param DefinedArgument $defined
     * @return void
     */
    protected function addAsDefaultArgument(DefinedArgument $defined): void
    {
        $name = $defined->getName();
        $this->parsedArguments[$name] = new Argument($defined, false);
        $default = $defined->getDefault();
        if ($defined->isArray()) {
            if (!is_array($default)) {
                throw new ParseException("Argument: {$name}'s default value must be an array, since it's a multi argument.", [
                    'argument' => $defined,
                    'default' => $default,
                ]);
            }
            foreach ($default as $value) {
                $this->parsedArguments[$name]->addValue($value);
            }
        } else {
            if (!is_string($default) && !is_null($default)) {
                throw new ParseException("Argument: {$name}'s default value must be defined as string.", [
                    'argument' => $defined,
                    'default' => $default,
                ]);
            }
            $this->parsedArguments[$name]->addValue($default);
        }
    }

    /**
     * @param DefinedOption $defined
     * @param mixed $value
     * @return void
     */
    protected function addAsOption(
        DefinedOption $defined,
        mixed $value
    ): void
    {
        $name = $defined->getName();
        $this->parsedOptions[$name] ??= new Option($defined, true);
        $this->parsedOptions[$name]->addValue($value);
    }

    /**
     * @param DefinedOption $defined
     * @return void
     */
    protected function addAsDefaultOption(DefinedOption $defined): void
    {
        $name = $defined->getName();
        $default = $defined->getDefault();

        if ($defined->isArray()) {
            $default ??= [];
            if (!is_array($default)) {
                throw new ParseException("Option: --{$name}'s default value must be an array, since it's marked as multi.", [
                    'option' => $defined,
                    'default' => $default,
                ]);
            }
        }
        elseif (!is_string($default) && !is_null($default)) {
            throw new ParseException("Option: --{$name}'s default value must be defined as string.", [
                'option' => $defined,
                'default' => $default,
            ]);
        }

        $values = !is_array($default) ? [$default] : $default;

        $this->parsedOptions[$name] = new Option($defined, false, $values);
    }
}
