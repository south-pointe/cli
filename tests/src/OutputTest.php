<?php declare(strict_types=1);

namespace Tests\SouthPointe\Cli;

use function dump;
use function error_get_last;
use const FILTER_VALIDATE_INT;

class OutputTest extends TestCase
{
    public function test_line(): void
    {
        $str = "1231111111111111111111111";
        $var = filter_var($str, FILTER_VALIDATE_INT);
        dump($var);
        dump(error_get_last());
    }
}
