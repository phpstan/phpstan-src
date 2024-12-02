<?php

namespace Bug11418;

function foo(int $a, int $b, int $c = 3, int $d = 4): int {
	return $a + $b + $c + $d;
}

var_dump(foo(...[1, 2], d: 40)); // 46
