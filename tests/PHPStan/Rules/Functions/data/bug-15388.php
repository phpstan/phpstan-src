<?php declare(strict_types = 1);

namespace Bug15388ReturnType;

/** @param list<int> $vals */
function foo(array $vals): int {
	return array_reduce($vals, function($carry, $val) {
		return $carry + map($val);
	}, 0);
}

/** @param list<int> $vals */
function bar(array $vals): int {
	return array_reduce($vals, function($carry, $val) {
		return $carry + $val;
	}, 0);
}

function map(int $val): int {
	return $val;
}
