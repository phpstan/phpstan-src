<?php declare(strict_types = 1);

namespace Bug7538;

$a = new \stdClass;
$b = new \stdClass;
$a % $b;
