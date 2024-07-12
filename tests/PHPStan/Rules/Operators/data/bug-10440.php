<?php declare(strict_types = 1);

namespace Bug10440;

$a = [];
$b = [''];

var_dump($a % $b);
