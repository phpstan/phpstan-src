<?php declare(strict_types = 1);

$a = ['first' => 'name', 'last' => null, [], new stdClass()];
$b = ['last' => null];
$diff = array_diff_assoc($a, $b);
var_dump($diff);

array_diff_assoc(['same' => new stdClass()], ['same' => null]);
array_diff_assoc(['same' => null], ['same' => new stdClass()]);
