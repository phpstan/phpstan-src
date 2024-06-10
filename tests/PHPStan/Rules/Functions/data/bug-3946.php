<?php declare(strict_types = 1);

namespace Bug3946;

$std = new stdClass();
$a = array('green', 1, 3.14, ['test'], $std);
$b = array('avocado', 'apple', 'banana', 'value', 'std value');
$c = array_combine($a, $b);
