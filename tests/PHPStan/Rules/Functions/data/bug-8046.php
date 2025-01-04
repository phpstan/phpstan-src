<?php declare(strict_types = 1);

namespace Bug8046;

function add(int $a, int $b): int {
	return $a + $b;
}

$args = ['a' => 7];

var_dump(add(...$args, b: 8));
