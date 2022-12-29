<?php declare(strict_types=1);

namespace Tests\SouthPointe\Cli;

use Prophecy\Prophecy\ObjectProphecy;
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

    public function test_plain_undefined_arg(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid Argument: "invalid" at [0]');
        $this->parseBuilder($this->makeBuilder(), ['invalid']);
    }

    public function test_argument(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a');
        $parameters = $this->parseBuilder($builder, ['1']);

        self::assertCount(1, $parameters->arguments);
        self::assertCount(0, $parameters->options);

        self::assertTrue($parameters->hasArgument('a'));
        self::assertSame('1', $parameters->getArgument('a'));
    }

    public function test_argument_as_optional(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->optional();
        $parameters = $this->parseBuilder($builder, []);

        self::assertCount(0, $parameters->arguments);
        self::assertFalse($parameters->hasArgument('a'));
    }

    public function test_argument_as_optional_with_default(): void
    {
        $builder = $this->makeBuilder();
        $builder->argument('a')->optional('default');
        $parameters = $this->parseBuilder($builder, []);

        self::assertCount(1, $parameters->arguments);
        self::assertTrue($parameters->hasArgument('a'));
    }

    public function test_argument_disallow_multi(): void
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
}
