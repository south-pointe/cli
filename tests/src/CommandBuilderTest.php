<?php declare(strict_types=1);

namespace Tests\SouthPointe\Cli;

use SouthPointe\Cli\CommandBuilder;
use SouthPointe\Cli\Exceptions\ParseException;
use SouthPointe\Cli\Parameters;
use SouthPointe\Cli\Parameters\ParameterParser;

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
     * @return Parameters
     */
    protected function parseBuilder(
        CommandBuilder $builder,
        array $rawParameters,
    ): Parameters
    {
        $parser = new ParameterParser($builder->build(), $rawParameters);
        $parsed = $parser->parse();
        return new Parameters($parsed['arguments'], $parsed['options']);
    }

    public function test_plain(): void
    {
        $builder = $this->makeBuilder();

        self::assertSame('test', $builder->build()->getName());

        $parameters = $this->parseBuilder($builder, []);

        self::assertCount(0, $parameters->arguments);
        self::assertCount(0, $parameters->options);
    }

    public function test_argument(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a');
        $parameters = $this->parseBuilder($builder, ['1']);

        self::assertCount(1, $parameters->arguments);
        self::assertCount(0, $parameters->options);

        self::assertTrue($parameters->argumentEntered('a'));
        self::assertSame(['1'], $parameters->getArgument('a')->getValues());
    }

    public function test_argument_missing(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid Argument: "invalid" at [0]');
        $this->parseBuilder($this->makeBuilder(), ['invalid']);
    }

    public function test_argument_missing_after_another_argument(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid Argument: "3" at [2]');
        $builder = $this->makeBuilder();
        $builder->argument('a');
        $builder->argument('b');
        $this->parseBuilder($builder, ['1', '2', '3']);
    }

    public function test_argument_as_optional(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->optional();
        $parameters = $this->parseBuilder($builder, []);

        self::assertCount(1, $parameters->arguments);
        self::assertFalse($parameters->argumentEntered('a'));
    }

    public function test_argument_as_optional_with_default_fallback(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->optional('1');
        $parameters = $this->parseBuilder($builder, []);

        self::assertCount(1, $parameters->arguments);
        self::assertFalse($parameters->argumentEntered('a'));
        self::assertSame(['1'], $parameters->getArgument('a')->getValues());
    }

    public function test_argument_as_optional_with_default(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->optional('1');
        $parameters = $this->parseBuilder($builder, ['x']);

        self::assertCount(1, $parameters->arguments);
        self::assertTrue($parameters->argumentEntered('a'));
        self::assertSame(['x'], $parameters->getArgument('a')->getValues());
    }

    public function test_argument_with_multiple_definitions_with_optional_first_with_fallback(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->optional('1');
        $builder->argument('b');
        $parameters = $this->parseBuilder($builder, ['x', 'y']);

        self::assertCount(2, $parameters->arguments);
        self::assertTrue($parameters->argumentEntered('a'));
        self::assertTrue($parameters->argumentEntered('b'));
        self::assertSame(['x'], $parameters->getArgument('a')->getValues());
        self::assertSame(['y'], $parameters->getArgument('b')->getValues());
    }

    public function test_argument_with_multiple_definitions_with_optional_first(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Missing required argument: b');
        $builder = $this->makeBuilder();
        $builder->argument('a')->optional('1');
        $builder->argument('b');
        $this->parseBuilder($builder, []);
    }

    public function test_argument_with_multiple_definitions_with_optionals_no_entry(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->optional('1');
        $builder->argument('b')->optional('2');
        $parameters = $this->parseBuilder($builder, []);

        self::assertCount(2, $parameters->arguments);
        self::assertFalse($parameters->argumentEntered('a'));
        self::assertFalse($parameters->argumentEntered('b'));
        self::assertSame(['1'], $parameters->getArgument('a')->getValues());
        self::assertSame(['2'], $parameters->getArgument('b')->getValues());
    }

    public function test_argument_overflow(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid Argument: "2" at [1]');
        $builder = $this->makeBuilder();
        $builder->argument('a');
        $this->parseBuilder($builder, ['1', '2']);
    }

    public function test_argument_multi(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->allowMultiple();
        $parameters = $this->parseBuilder($builder, ['1', '2']);

        self::assertCount(1, $parameters->arguments);
        self::assertCount(0, $parameters->options);
    }

    public function test_argument_multi_after_single(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a');
        $builder->argument('b')->allowMultiple();
        $parameters = $this->parseBuilder($builder, ['1', '2', '3']);

        self::assertCount(2, $parameters->arguments);
        self::assertSame(['1'], $parameters->getArgument('a')->getValues());
        self::assertSame(['2', '3'], $parameters->getArgument('b')->getValues());
    }

    public function test_argument_single_after_multi(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Missing required argument: b');
        $builder = $this->makeBuilder();
        $builder->argument('a')->allowMultiple();
        $builder->argument('b');
        $this->parseBuilder($builder, ['1', '2', '3']);
    }

    public function test_argument_single_with_default_after_multi(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->allowMultiple();
        $builder->argument('b')->optional('4');
        $parameters = $this->parseBuilder($builder, ['1', '2', '3']);

        self::assertSame(['1', '2', '3'], $parameters->getArgument('a')->getValues());
        self::assertSame(['4'], $parameters->getArgument('b')->getValues());
    }

    public function test_option__long__no_value(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all');
        $parameters = $this->parseBuilder($builder, ['--all']);
        self::assertCount(1, $parameters->options);
        self::assertTrue($parameters->getOption('all')->wasEntered());
        self::assertSame('all', $parameters->getOption('all')->getEnteredName());
        self::assertSame([null], $parameters->getOption('all')->getValues());
    }

    public function test_option__long__no_value__default(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all')->optional();
        $parameters = $this->parseBuilder($builder, ['--all']);
        self::assertCount(1, $parameters->options);
        self::assertTrue($parameters->getOption('all')->wasEntered());
        self::assertSame('all', $parameters->getOption('all')->getEnteredName());
        self::assertSame([null], $parameters->getOption('all')->getValues());
    }

    public function test_option__long__spaced_value(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all');
        $parameters = $this->parseBuilder($builder, ['--all', 'text']);
        self::assertCount(1, $parameters->options);
        self::assertTrue($parameters->getOption('all')->wasEntered());
        self::assertSame('all', $parameters->getOption('all')->getEnteredName());
        self::assertSame(['text'], $parameters->getOption('all')->getValues());
    }

    public function test_option__long__equal_value(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all');
        $parameters = $this->parseBuilder($builder, ['--all=text']);
        self::assertCount(0, $parameters->arguments);
        self::assertCount(1, $parameters->options);
        self::assertTrue($parameters->getOption('all')->wasEntered());
        self::assertSame('all', $parameters->getOption('all')->getEnteredName());
        self::assertSame(['text'], $parameters->getOption('all')->getValues());
    }

    public function test_option__short__no_value(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all', 'a');
        $parameters = $this->parseBuilder($builder, ['-a']);
        self::assertCount(1, $parameters->options);
        self::assertTrue($parameters->getOption('all')->wasEntered());
        self::assertSame('a', $parameters->getOption('all')->getEnteredName());
        self::assertSame([null], $parameters->getOption('all')->getValues());
    }

    public function test_option__short__spaced_value(): void
    {
        $builder = $this->makeBuilder();
        $builder->option('all', 'a');
        $parameters = $this->parseBuilder($builder, ['-a', 'text']);
        self::assertCount(1, $parameters->options);
        self::assertTrue($parameters->getOption('all')->wasEntered());
        self::assertSame('a', $parameters->getOption('all')->getEnteredName());
        self::assertSame(['text'], $parameters->getOption('all')->getValues());
    }

    public function test_option__short__equal_value(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Option: -a (--all) invalid value: "=text"');
        $builder = $this->makeBuilder();
        $builder->option('all', 'a');
        $this->parseBuilder($builder, ['-a=text']);
    }
}
