<?php declare(strict_types=1);

namespace SouthPointe\Cli\Parameters;

use SouthPointe\Cli\CommandDefinition;
use SouthPointe\Cli\Definitions\ArgumentDefinition;
use SouthPointe\Cli\Definitions\OptionDefinition;
use SouthPointe\Cli\Definitions\ParameterDefinition;
use SouthPointe\Cli\Exceptions\ParseException;
use function array_diff_key;
use function array_is_list;
use function array_slice;
use function count;
use function explode;
use function gettype;
use function is_array;
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
     * @var array<string, list<mixed>>
     */
    protected array $argumentValues = [];

    /**
     * @var array<string, list<mixed>>
     */
    protected array $optionValues = [];

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

        $arguments = [];
        foreach ($this->argumentValues as $name => $values) {
            $defined = $this->definition->getArgumentByName($name);
            $arguments[$name] = new Argument($defined, true, $values);
        }
        $arguments = $this->appendRemainingArguments($arguments);

        $options = [];
        foreach ($this->optionValues as $name => $values) {
            $defined = $this->getDefinedOption($name);
            $options[$name] = new Option($defined, true, $values);
        }
        $all = $this->definition->getOptions();
        $remaining = array_diff_key($all, $options);
        foreach ($remaining as $name => $defined) {
            $options[$name] = $this->makeDefaultOption($defined);
        }

        return [
            'arguments' => $arguments,
            'options' => $options,
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
        return (bool) preg_match('/--\w+/', $parameter);
    }

    /**
     * @param string $parameter
     * @return bool
     */
    protected function isShortOption(string $parameter): bool
    {
        return (bool) preg_match('/-\w+/', $parameter);
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

        $defined = $this->getDefinedOption($name);

        if ($value === null && $this->hasMoreParameters()) {
            // look at the next parameter to check if it's a value
            $nextParameter = $this->nextParameterOrNull();
            if ($nextParameter !== null && $this->isNotAnOption($nextParameter)) {
                $value = $nextParameter;
                $this->parameterCursor++;
            }
        }

        $value ??= $defined->default;

        if ($value === null && $defined->valueRequired) {
            throw new ParseException("Option: [--{$name}] requires a value.", [
                'option' => $defined,
                'parameter' => $parameter,
            ]);
        }

        $this->addOptionValue($defined, $value);

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

            $nextChar = $chars[$i + 1] ?? false;

            // on the last char, no need to go further.
            if ($nextChar === false) {
                $nextParameter = $this->nextParameterOrNull();
                if ($nextParameter !== null && $this->isNotAnOption($nextParameter)) {
                    $this->addOptionValue($defined, $nextParameter);
                    $this->parameterCursor += 2;
                    break;
                }

                $default = $defined->default;

                if ($default === null && $defined->valueRequired) {
                    throw new ParseException("Option: [-{$char}] requires a value.", [
                        'option' => $defined,
                        'parameter' => $parameter,
                    ]);
                }

                $this->addOptionValue($defined, $default);
                $this->parameterCursor++;
                break;
            }

            // if next char is another option, add the current option and move on.
            if ($this->definition->shortOptionExists($nextChar)) {
                $this->addOptionValue($defined, $defined->default);
                $this->parameterCursor++;
                continue;
            }

            // if next char is not an option, assume it's an argument.
            $remainingChars = substr($chars, $i + 1);
            if ($defined->valueRequired) {
                $this->addOptionValue($defined, $remainingChars);
                $this->parameterCursor++;
                break;
            }

            throw new ParseException("[option: -{$char} (--{$defined->name})] invalid value: \"{$remainingChars}\"", [
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

        $this->argumentValues[$defined->name][] = $parameter;

        if (!$defined->allowMultiple) {
            $this->argumentCursor++;
        }

        $this->parameterCursor++;
    }

    /**
     * @param array<string, Argument> $arguments
     * @return array<string, Argument>
     */
    protected function appendRemainingArguments(array $arguments): array
    {
        $all = $this->definition->getArguments();
        $remaining = array_slice($all, $this->argumentCursor, null, true);
        foreach ($remaining as $name => $argument) {
            if ($argument->allowMultiple && isset($this->argumentValues[$name])) {
                continue;
            }

            if (!$argument->optional) {
                throw new ParseException("Missing required argument: {$name}", [
                    'parameters' => $this->parameters,
                    'argument' => $argument,
                ]);
            }

            $arguments[$name] = $this->makeDefaultArgument($argument);
        }

        return $arguments;
    }

    /**
     * @param string $name
     * @return OptionDefinition
     */
    protected function getDefinedOption(string $name): OptionDefinition
    {
        if ($this->definition->optionExists($name)) {
            return $this->checkOptionCount($this->definition->getOption($name));
        }

        throw new ParseException("Option: [--{$name}] is not defined.", [
            'parameters' => $this->parameters,
            'name' => $name,
        ]);
    }

    /**
     * @param string $char
     * @return OptionDefinition
     */
    protected function getDefinedShortOption(string $char): OptionDefinition
    {
        if ($this->definition->shortOptionExists($char)) {
            return $this->checkOptionCount($this->definition->getShortOption($char));
        }

        throw new ParseException("Option: [-{$char}] is not defined.", [
            'parameters' => $this->parameters,
            'cursor' => $this->parameterCursor,
            'char' => $char,
        ]);
    }

    /**
     * @param OptionDefinition $option
     * @return OptionDefinition
     */
    protected function checkOptionCount(OptionDefinition $option): OptionDefinition
    {
        $name = $option->name;
        $values = $this->optionValues[$name] ?? [];

        if (count($values) > 1 && !$option->allowMultiple) {
            throw new ParseException("Option: [--{$name}] cannot be entered more than once", [
                'option' => $option,
                'parameters' => $this->parameters,
            ]);
        }

        return $option;
    }

    /**
     * @param ArgumentDefinition $defined
     * @return Argument
     */
    protected function makeDefaultArgument(ArgumentDefinition $defined): Argument
    {
        return new Argument($defined, false, $this->getDefaultValue($defined));
    }

    /**
     * @param OptionDefinition $defined
     * @param mixed $value
     * @return void
     */
    protected function addOptionValue(
        OptionDefinition $defined,
        mixed $value
    ): void
    {
        $this->optionValues[$defined->name][] = $value;
    }

    /**
     * @param OptionDefinition $defined
     * @return Option
     */
    protected function makeDefaultOption(OptionDefinition $defined): Option
    {
        return new Option($defined, false, $this->getDefaultValue($defined));
    }

    /**
     * @param ParameterDefinition $defined
     * @return list<string>
     */
    protected function getDefaultValue(ParameterDefinition $defined): array
    {
        $default = $defined->default;

        if ($defined->allowMultiple) {
            $default ??= [];

            if (!is_array($default)) {
                $this->throwParseException($defined, 'Default values must be list<string>, since it allows multiple values.');
            }

            if (!array_is_list($default)) {
                $this->throwParseException($defined, 'Default values must be list<string>, map given.');
            }

            foreach ($default as $value) {
                if (!is_string($value)) {
                    $type = gettype($value);
                    $this->throwParseException($defined, "Default values must consist of strings, {$type} given.");
                }
            }
            return $default;
        }

        $default ??= '';
        if (!is_string($default)) {
            $type = gettype($default);
            $this->throwParseException($defined, "Default value must be defined as string, {$type} given.");
        }
        return [$default];
    }

    /**
     * @param ParameterDefinition $defined
     * @param string $message
     * @param array<string, mixed> $context
     * @return never
     */
    protected function throwParseException(ParameterDefinition $defined, string $message, array $context = []): never
    {
        $type = ($defined instanceof ArgumentDefinition)
            ? "Option: [--{$defined->name}]"
            : "Argument: [{$defined->name}]";

        throw new ParseException("{$type} {$message}", $context + [
            'defined' => $defined,
        ]);
    }
}
