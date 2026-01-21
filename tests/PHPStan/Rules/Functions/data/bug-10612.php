<?php declare(strict_types = 1);

namespace Bug10612;

/**
 * @template K of array-key
 * @template V
 * @template RK of array-key
 * @template RV
 * @param array<K, V> $source
 * @param callable(V, K): array{RK, RV} $transform
 * @return array<RK, RV>
 */
function associate(array $source, callable $transform): array
{
	$result = [];

	foreach ($source as $key => $value) {
		[$newKey, $newValue] = $transform($value, $key);
		$result[$newKey] = $newValue;
	}

	return $result;
}

function test(): void
{
	$a = associate(
		[[1], [2], [3]],
		function (array $entry): array {
			\PHPStan\dumpType($entry);
			[$key] = $entry;

			\PHPStan\dumpType($key);
			return [$key, $key];
		}
	);

	\PHPStan\dumpType($a);
}
