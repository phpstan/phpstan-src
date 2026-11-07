<?php declare(strict_types = 1);

namespace Bug15388;

use function PHPStan\Testing\assertType;

/** @param list<int> $vals */
function foo(array $vals): int {
	$result = array_reduce($vals, function($carry, $val) {
		assertType('int', $carry + map($val));
		return $carry + map($val);
	}, 0);
	assertType('int', $result);

	return $result;
}

function map(int $val): int {
	return $val;
}

/** @param list<int> $vals */
function bar(array $vals): int {
	$result = array_reduce($vals, function($carry, $val) {
		assertType('int', $carry + $val);
		return $carry + $val;
	}, 0);
	assertType('int', $result);

	return $result;
}
