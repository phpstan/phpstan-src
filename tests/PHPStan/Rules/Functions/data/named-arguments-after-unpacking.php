<?php

namespace FunctionNamedArgumentsAfterUnpacking;

// https://www.php.net/manual/en/functions.arguments.php#example-180

function foo($a, $b, $c = 3, $d = 4) {
	return $a + $b + $c + $d;
}

var_dump(foo(...[1, 2], d: 40)); // 46
var_dump(foo(...['b' => 2, 'a' => 1], d: 40)); // 46

var_dump(foo(...[1, 2], b: 20)); // Fatal error. Named parameter $b overwrites previous argument
