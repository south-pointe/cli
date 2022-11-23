<?php declare(strict_types=1);

namespace Tests\SouthPointe\Cli;

use SouthPointe\Cli\Output;

class OutputTest extends TestCase
{
    public function test_line(): void
    {
        $output = new Output();
        $output->debug("asdf");
        $output->error("DEfas");
    }
}
