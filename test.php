<?php

use SouthPointe\Ansi\Stream;
use SouthPointe\Cli\Input;
use SouthPointe\Cli\Output;

require './vendor/autoload.php';

$output = new Output(new Stream());
$input = new Input($output);
dump($input->masked('in:'));
