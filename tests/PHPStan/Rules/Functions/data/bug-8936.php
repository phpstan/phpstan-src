<?php declare(strict_types = 1);

namespace Bug8936;

use \Ds\Map;
use \Ds\Pair;

/**
 * @param iterable<V> $iterable
 * @param callable(V): Pair<KReturn, VReturn> $mapper
 *
 * @return Map<KReturn, VReturn>
 *
 * @template V
 * @template KReturn
 * @template VReturn
 */
function foo(iterable $iterable, callable $mapper): Map
{
	/** @var Map<KReturn, VReturn> $map */
	$map = new Map();

	foreach ($iterable as $value) {
		$keyValue = $mapper($value);
		$map->put($keyValue->key, $keyValue->value);
	}

	return $map;
}

/** @var list<array{a: int}> $data */
$data;

/** @var Map<string, array{a: int}> $map */
$map = foo(
	$data,
	static fn (array $entry) => new Pair($entry['a'], $entry),
);
