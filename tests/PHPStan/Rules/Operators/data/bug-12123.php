<?php declare(strict_types = 1);

namespace Bug12123;

$x = gmp_init('1');
$y = $x * 2;
var_dump($y);
