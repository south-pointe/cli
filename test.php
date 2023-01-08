<?php

use SouthPointe\Ansi\Stream;
use SouthPointe\Cli\AnsiDecorator;
use SouthPointe\Cli\Input;

require './vendor/autoload.php';

$output = new AnsiDecorator(new Stream());
$input = new Input($output);
dump($input->masked('in:'));
