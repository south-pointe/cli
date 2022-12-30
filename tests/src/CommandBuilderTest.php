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

    public function test_argument_as_optional_with_default(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->optional('default');
        $parameters = $this->parseBuilder($builder, []);

        self::assertCount(1, $parameters->arguments);
        self::assertFalse($parameters->argumentEntered('a'));
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
}
