<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug11982;

/**
 * @template V The input value type
 * @template V2 The output value type
 *
 * @param list<V> $iterable
 * @param callable(V): V2 $callback
 *
 * @return list<V2>
 */
function map($iterable, $callback) {
	$result = [];
	foreach ($iterable as $v) {
		$result[] = $callback($v);
	}
	return $result;
}

// Works
$x = map([1,2,3], fn ($value) => ['n' => $value]);

// Works too:
$x = map(range(1,3), fn ($value) => ['n' => $value]);

// Does not work:
$x = map(range(1,100), fn ($value) => ['n' => $value]);

// Does not work either:
$x = map(range(1,100), fn (int $value) => ['n' => $value]);

/** @return array{n: int} */
function mapper(int $n) {
	return ['n' => $n];
}
// Works again:
$x = map(range(1,100), mapper(...));
