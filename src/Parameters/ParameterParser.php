<?php declare(strict_types=1);

namespace SouthPointe\Cli\Parameters;

use SouthPointe\Cli\CommandDefinition;
use SouthPointe\Cli\Definitions\ArgumentDefinition;
use SouthPointe\Cli\Definitions\OptionDefinition;
use SouthPointe\Cli\Definitions\ParameterDefinition;
use SouthPointe\Cli\Exceptions\ParseException;
use function array_is_list;
use function array_key_exists;
use function count;
use function explode;
use function gettype;
use function is_array;
use function is_string;
use function ltrim;
use function preg_match;
use function str_starts_with;
use function strlen;
use function substr;

class ParameterParser
{
    /**
     * @param CommandDefinition $definition
     * @param list<string> $parameters
     * @return array{
     *     arguments: array<string, Argument>,
     *     options: array<string, Option>,
     * }
     */
    public static function parse(CommandDefinition $definition, array $parameters): array
    {
        $self = new self($definition, $parameters);
        return $self->execute();
    }

    /**
     * @param CommandDefinition $definition
     * @param list<string> $parameters
     * @param int $argumentCursor
     * @param int $parameterCursor
     * @param array<string, list<mixed>> $argumentValues
     * @param array<string, list<mixed>> $optionValues
     */
    protected function __construct(
        protected CommandDefinition $definition,
        protected array $parameters,
        protected int $argumentCursor = 0,
        protected int $parameterCursor = 0,
        protected array $argumentValues = [],
        protected array $optionValues = [],
    )
    {
    }

    /**
     * @return array{
     *     arguments: array<string, Argument>,
     *     options: array<string, Option>,
     * }
     */
    public function execute(): array
    {
        $this->processParameters();
        
        return [
            'arguments' => $this->makeArguments(),
            'options' => $this->makeOptions(),
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
        return (bool) preg_match('/^--\w+/', $parameter);
    }

    /**
     * @param string $parameter
     * @return bool
     */
    protected function isShortOption(string $parameter): bool
    {
        return (bool) preg_match('/^-\w+/', $parameter);
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
     * @return void
     */
    protected function processParameters(): void
    {
        $parameterCount = count($this->parameters);
        $parameters = $this->parameters;
        while ($this->parameterCursor < $parameterCount) {
            $parameter = $parameters[$this->parameterCursor];
            match (true) {
                $this->isLongOption($parameter) => $this->processAsLongOption($parameter),
                $this->isShortOption($parameter) => $this->processAsShortOptions($parameter),
                default => $this->processAsArgument($parameter),
            };
        }
    }

    /**
     * @param string $parameter
     * @return void
     */
    protected function processAsLongOption(string $parameter): void
    {
        $parts = explode('=', ltrim($parameter, '-'));
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
                'defined' => $defined,
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
    protected function processAsShortOptions(string $parameter): void
    {
        $chars = ltrim($parameter, '-');

        for ($i = 0, $size = strlen($chars); $i < $size; $i++) {
            $char = $chars[$i];
            $defined = $this->getDefinedOptionByShortName($char);

            $nextChar = $chars[$i + 1] ?? false;

            // on the last char, no need to go further.
            if ($nextChar === false) {
                $nextParameter = $this->nextParameterOrNull();
                if ($nextParameter !== null && $this->isNotAnOption($nextParameter)) {
                    $this->addOptionValue($defined, $nextParameter);
                    $this->parameterCursor += 2;
                    break;
                }

                // No values defined. Use default value.
                $default = $defined->default;

                if ($default === null && $defined->valueRequired) {
                    throw new ParseException("Option: [-{$char}] requires a value.", [
                        'defined' => $defined,
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
    protected function processAsArgument(string $parameter): void
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
     * @return array<string, Argument>
     */
    protected function makeArguments(): array
    {
        $arguments = [];
        foreach ($this->definition->getArguments() as $name => $defined) {
            $values = $this->argumentValues[$name] ?? null;

            if ($values !== null) {
                $arguments[$name] = new Argument($defined, true, $values);
                continue;
            }

            if (!$defined->optional) {
                throw new ParseException("Missing required argument: {$name}.", [
                    'parameters' => $this->parameters,
                    'defined' => $defined,
                ]);
            }

            $arguments[$name] = new Argument($defined, false, $this->getDefaultValue($defined));
        }

        return $arguments;
    }

    /**
     * @return array<string, Option>
     */
    protected function makeOptions(): array
    {
        $options = [];
        foreach ($this->definition->getOptions() as $name => $defined) {
            $values = $this->optionValues[$name] ?? null;
            $options[$name] = $values !== null
                ? new Option($defined, true, $values)
                : new Option($defined, false, $this->getDefaultValue($defined));
        }
        return $options;
    }

    /**
     * @param string $name
     * @return OptionDefinition
     */
    protected function getDefinedOption(string $name): OptionDefinition
    {
        $defined = $this->definition->getOptionOrNull($name);

        if ($defined === null) {
            throw new ParseException("Option: [--{$name}] is not defined.", [
                'parameters' => $this->parameters,
                'name' => $name,
            ]);
        }

        return $defined;
    }

    /**
     * @param string $char
     * @return OptionDefinition
     */
    protected function getDefinedOptionByShortName(string $char): OptionDefinition
    {
        $defined = $this->definition->getOptionByShortOrNull($char);

        if ($defined === null) {
            throw new ParseException("Option: [-{$char}] is not defined.", [
                'parameters' => $this->parameters,
                'cursor' => $this->parameterCursor,
                'char' => $char,
            ]);
        }

        return $defined;
    }

    /**
     * @param OptionDefinition $defined
     * @param mixed $value
     * @return void
     */
    protected function addOptionValue(OptionDefinition $defined, mixed $value): void
    {
        $name = $defined->name;

        if (array_key_exists($name, $this->optionValues) && !$defined->allowMultiple) {
            throw new ParseException("Option: [--{$name}] cannot be entered more than once.", [
                'defined' => $defined,
                'parameters' => $this->parameters,
            ]);
        }

        $this->optionValues[$name][] = $value ?? '';
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
