<?php declare(strict_types=1);

namespace Tests\SouthPointe\Cli;

use SouthPointe\Cli\CommandBuilder;
use SouthPointe\Cli\Exceptions\ParseException;
use SouthPointe\Cli\Parameters\Argument;
use SouthPointe\Cli\Parameters\Option;
use SouthPointe\Cli\Parameters\ParameterParser;
use SouthPointe\Core\Exceptions\LogicException;

class CommandBuilderTest extends TestCase
{
    /**
     * @param string $name
     * @return CommandBuilder
     */
    protected function makeBuilder(string $name = 'test'): CommandBuilder
    {
        $builder = new CommandBuilder();
        $builder->name($name);
        return $builder;
    }

    /**
     * @param CommandBuilder $builder
     * @param list<string> $rawParameters
     * @return array{
     *     arguments: array<string, Argument>,
     *     options: array<string, Option>,
     * }
     */
    protected function parse(
        CommandBuilder $builder,
        array $rawParameters,
    ): array
    {
        $parser = new ParameterParser($builder->build(), $rawParameters);
        return $parser->parse();
    }

    public function test_plain(): void
    {
        $builder = $this->makeBuilder();

        self::assertSame('test', $builder->build()->getName());

        $parsed = $this->parse($builder, []);

        self::assertCount(0, $parsed['arguments']);
        self::assertCount(0, $parsed['options']);
    }

    public function test_argument(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a');
        $parsed = $this->parse($builder, ['1']);

        self::assertCount(1, $parsed['arguments']);
        self::assertCount(0, $parsed['options']);

        $argument = $parsed['arguments']['a'];
        self::assertTrue($argument->wasEntered());
        self::assertSame(['1'], $argument->getValues());
    }

    public function test_argument_missing(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Argument [0: "invalid"] is not defined.');
        $this->parse($this->makeBuilder(), ['invalid']);
    }

    public function test_argument_missing_after_another_argument(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Argument [2: "3"] is not defined.');
        $builder = $this->makeBuilder();
        $builder->argument('a');
        $builder->argument('b');
        $this->parse($builder, ['1', '2', '3']);
    }

    public function test_argument__name_collision(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Argument [a] already exists.');
        $builder = $this->makeBuilder();
        $builder->name('t');
        $builder->argument('a');
        $builder->argument('a');
    }

    public function test_argument_as_optional(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->optional();
        $parsed = $this->parse($builder, []);

        self::assertCount(1, $parsed['arguments']);
        self::assertFalse($parsed['arguments']['a']->wasEntered());
    }

    public function test_argument_as_optional_with_default_fallback(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->optional('1');
        $parsed = $this->parse($builder, []);

        self::assertCount(1, $parsed['arguments']);

        $argument = $parsed['arguments']['a'];
        self::assertFalse($argument->wasEntered());
        self::assertSame(['1'], $argument->getValues());
    }

    public function test_argument_as_optional_with_default(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->optional('1');
        $parsed = $this->parse($builder, ['x']);

        self::assertCount(1, $parsed['arguments']);

        $argument = $parsed['arguments']['a'];
        self::assertTrue($argument->wasEntered());
        self::assertSame(['x'], $argument->getValues());
    }

    public function test_argument_with_multiple_definitions_with_optional_first_with_fallback(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->optional('1');
        $builder->argument('b');
        $parsed = $this->parse($builder, ['x', 'y']);

        self::assertCount(2, $parsed['arguments']);

        $argument_a = $parsed['arguments']['a'];
        $argument_b = $parsed['arguments']['b'];
        self::assertTrue($argument_a->wasEntered());
        self::assertTrue($argument_b->wasEntered());
        self::assertSame(['x'], $argument_a->getValues());
        self::assertSame(['y'], $argument_b->getValues());
    }

    public function test_argument_with_multiple_definitions_with_optional_first(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Missing required argument: b');
        $builder = $this->makeBuilder();
        $builder->argument('a')->optional('1');
        $builder->argument('b');
        $this->parse($builder, []);
    }

    public function test_argument_with_multiple_definitions_with_optionals_no_entry(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->optional('1');
        $builder->argument('b')->optional('2');
        $parsed = $this->parse($builder, []);

        self::assertCount(2, $parsed['arguments']);

        $argument_a = $parsed['arguments']['a'];
        $argument_b = $parsed['arguments']['b'];
        self::assertFalse($argument_a->wasEntered());
        self::assertFalse($argument_b->wasEntered());
        self::assertSame(['1'], $argument_a->getValues());
        self::assertSame(['2'], $argument_b->getValues());
    }

    public function test_argument_overflow(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Argument [1: "2"] is not defined.');
        $builder = $this->makeBuilder();
        $builder->argument('a');
        $this->parse($builder, ['1', '2']);
    }

    public function test_argument_multi(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->allowMultiple();
        $parsed = $this->parse($builder, ['1', '2']);

        self::assertCount(1, $parsed['arguments']);
        self::assertCount(0, $parsed['options']);
    }

    public function test_argument_multi_after_single(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a');
        $builder->argument('b')->allowMultiple();
        $parsed = $this->parse($builder, ['1', '2', '3']);

        self::assertCount(2, $parsed['arguments']);

        $argument_a = $parsed['arguments']['a'];
        $argument_b = $parsed['arguments']['b'];
        self::assertSame(['1'], $argument_a->getValues());
        self::assertSame(['2', '3'], $argument_b->getValues());
    }

    public function test_argument_single_after_multi(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Missing required argument: b');
        $builder = $this->makeBuilder();
        $builder->argument('a')->allowMultiple();
        $builder->argument('b');
        $this->parse($builder, ['1', '2', '3']);
    }

    public function test_argument_single_with_default_after_multi(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->allowMultiple();
        $builder->argument('b')->optional('4');
        $parsed = $this->parse($builder, ['1', '2', '3']);

        $argument_a = $parsed['arguments']['a'];
        $argument_b = $parsed['arguments']['b'];
        self::assertSame(['1', '2', '3'], $argument_a->getValues());
        self::assertSame(['4'], $argument_b->getValues());
    }

    public function test_option__long__name_collision(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Option [--a] already exists.');
        $builder = $this->makeBuilder();
        $builder->name('t');
        $builder->option('a');
        $builder->option('a');
    }

    public function test_option__short__name_collision(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Option [-a] already exists.');
        $builder = $this->makeBuilder();
        $builder->name('t');
        $builder->option('one', 'a');
        $builder->option('two', 'a');
    }

    public function test_option__long__undefined(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Option: [--all] is not defined.');
        $builder = $this->makeBuilder();
        $this->parse($builder, ['--all']);
    }

    public function test_option__long__no_value(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all');
        $parsed = $this->parse($builder, ['--all']);

        self::assertCount(1, $parsed['options']);

        $option = $parsed['options']['all'];
        self::assertTrue($option->wasEntered());
        self::assertSame([null], $option->getValues());
    }

    public function test_option__long__no_value__default(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all')->requiresValue('d');
        $parsed = $this->parse($builder, ['--all']);

        self::assertCount(1, $parsed['options']);

        $option = $parsed['options']['all'];
        self::assertTrue($option->wasEntered());
        self::assertSame(['d'], $option->getValues());
    }

    public function test_option__long__no_value__value_required(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Option: [--all] requires a value');
        $builder = $this->makeBuilder();
        $builder->option('all')->requiresValue();
        $this->parse($builder, ['--all']);
    }

    public function test_option__long__spaced_value(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all');
        $parsed = $this->parse($builder, ['--all', 'text']);

        self::assertCount(1, $parsed['options']);

        $option = $parsed['options']['all'];
        self::assertTrue($option->wasEntered());
        self::assertSame(['text'], $option->getValues());
    }

    public function test_option__long__equal_value(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all');
        $parsed = $this->parse($builder, ['--all=text']);

        self::assertCount(1, $parsed['options']);

        $option = $parsed['options']['all'];
        self::assertTrue($option->wasEntered());
        self::assertSame(['text'], $option->getValues());
    }

    public function test_option__long__multiple(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all')->allowMultiple();
        $parsed = $this->parse($builder, ['--all=1', '--all=2']);

        self::assertCount(1, $parsed['options']);

        $option = $parsed['options']['all'];
        self::assertTrue($option->wasEntered());
        self::assertSame(['1', '2'], $option->getValues());
    }

    public function test_option__long__multiple__no_input(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all')->allowMultiple();
        $parsed = $this->parse($builder, []);

        self::assertCount(1, $parsed['options']);

        $option = $parsed['options']['all'];
        self::assertFalse($option->wasEntered());
        self::assertSame([], $option->getValues());
    }

    public function test_option__long__multiple__default(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all')->allowMultiple()->requiresValue(['3']);
        $parsed = $this->parse($builder, []);

        self::assertCount(1, $parsed['options']);

        $option = $parsed['options']['all'];
        self::assertFalse($option->wasEntered());
        self::assertSame(['3'], $option->getValues());
    }

    public function test_option__long__multiple__default_no_fallback(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all')->allowMultiple()->requiresValue(['3']);
        $parsed = $this->parse($builder, ['--all=1']);

        self::assertCount(1, $parsed['options']);

        $option = $parsed['options']['all'];
        self::assertTrue($option->wasEntered());
        self::assertSame(['1'], $option->getValues());
    }

    public function test_option__long__multiple__with_other_options_in_between(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all')->allowMultiple();
        $builder->option('bee');
        $parsed = $this->parse($builder, ['--all=1', '--bee', '--all=2']);

        self::assertCount(2, $parsed['options']);

        $optionAll = $parsed['options']['all'];
        $optionBee = $parsed['options']['bee'];
        self::assertTrue($optionAll->wasEntered());
        self::assertSame(['1', '2'], $optionAll->getValues());
        self::assertSame([null], $optionBee->getValues());
    }

    public function test_option__short__undefined(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Option: [-a] is not defined.');
        $builder = $this->makeBuilder();
        $this->parse($builder, ['-a']);
    }

    public function test_option__short__no_value(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all', 'a');
        $parsed = $this->parse($builder, ['-a']);

        self::assertCount(1, $parsed['options']);

        $option = $parsed['options']['all'];
        self::assertTrue($option->wasEntered());
        self::assertSame([null], $option->getValues());
    }

    public function test_option__short__spaced_value(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all', 'a');
        $parsed = $this->parse($builder, ['-a', 'text']);

        self::assertCount(1, $parsed['options']);

        $option = $parsed['options']['all'];
        self::assertTrue($option->wasEntered());
        self::assertSame(['text'], $option->getValues());
    }

    public function test_option__short__equal_value(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('[option: -a (--all)] invalid value: "=text"');
        $builder = $this->makeBuilder();
        $builder->option('all', 'a');
        $this->parse($builder, ['-a=text']);
    }

    public function test_option__short__no_value__default(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all', 'a')->requiresValue('d');
        $parsed = $this->parse($builder, ['-a']);

        self::assertCount(1, $parsed['options']);

        $option = $parsed['options']['all'];
        self::assertTrue($option->wasEntered());
        self::assertSame(['d'],$option->getValues());
    }

    public function test_option__short__no_value__value_required(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Option: [-a] requires a value');
        $builder = $this->makeBuilder();
        $builder->option('all', 'a')->requiresValue();
        $this->parse($builder, ['-a']);
    }

    public function test_option__short__consecutive_chars(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all', 'a');
        $builder->option('bee', 'b');
        $parsed = $this->parse($builder, ['-ab']);

        self::assertCount(2, $parsed['options']);

        $optionAll = $parsed['options']['all'];
        $optionBee = $parsed['options']['bee'];
        self::assertTrue($optionAll->wasEntered());
        self::assertTrue($optionBee->wasEntered());
        self::assertSame([null], $optionAll->getValues());
        self::assertSame([null], $optionBee->getValues());
    }

    public function test_option__short__consecutive_chars__default(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all', 'a')->requiresValue('1');
        $builder->option('bee', 'b')->requiresValue('2');
        $parsed = $this->parse($builder, ['-ab']);

        self::assertCount(2, $parsed['options']);

        $optionAll = $parsed['options']['all'];
        $optionBee = $parsed['options']['bee'];
        self::assertTrue($optionAll->wasEntered());
        self::assertTrue($optionBee->wasEntered());
        self::assertSame(['1'], $optionAll->getValues());
        self::assertSame(['2'], $optionBee->getValues());
    }

    public function test_option__short__multiple(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all', 'a')->requiresValue('1');
        $builder->option('bee', 'b')->requiresValue('2');
        $parsed = $this->parse($builder, ['-ab']);

        self::assertCount(2, $parsed['options']);

        $optionAll = $parsed['options']['all'];
        $optionBee = $parsed['options']['bee'];
        self::assertTrue($optionAll->wasEntered());
        self::assertTrue($optionBee->wasEntered());
        self::assertSame(['1'],$optionAll->getValues());
        self::assertSame(['2'], $optionBee->getValues());
    }
}
