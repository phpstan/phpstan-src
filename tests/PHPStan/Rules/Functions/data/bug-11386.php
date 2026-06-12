<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug11386;

use Ds\Map;
use Ds\Pair;

/**
 * @param iterable<K, V> $iterable
 * @param callable(K, V): Pair<KReturn, VReturn> $mapper
 *
 * @return Map<KReturn, VReturn>
 *
 * @template K
 * @template V
 * @template KReturn
 * @template VReturn
 */
function mapFromIterable(iterable $iterable, callable $mapper): Map
{
	/** @var Map<KReturn, VReturn> $map */
	$map = new Map();

	foreach ($iterable as $key => $value) {
		$keyValue = $mapper($key, $value);
		$map->put($keyValue->key, $keyValue->value);
	}

	return $map;
}

/** @return Map<string, bool> */
function getMap(): Map
{
	/** @var array<array{id: string, bool: boolean}> */
	$result = [
		[
			'id' => 'c5a89f6f-5fc2-4444-b993-59d932bf869b',
			'bool' => true,
		]
	];

	\PHPStan\dumpType($result);

	return mapFromIterable(
		$result,
		static function (mixed $_, array $row) {
			\PHPStan\dumpType($row);

			return new Pair($row['id'], $row['bool']);
		}
	);
}
