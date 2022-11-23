<?php

use SouthPointe\Cli\Input;
use SouthPointe\Cli\Output;
use SouthPointe\Cli\Output\Ansi;

require './vendor/autoload.php';

$output = new Output($ansi = new Ansi());
$input = new Input($output);
//readline('');
dump($input->readline());
