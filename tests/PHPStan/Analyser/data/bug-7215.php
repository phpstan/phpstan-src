<?php declare(strict_types = 1);

namespace Bug7215;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param array<T,mixed> $array
 * @return (T is int ? ($array is non-empty-array ? non-empty-list<numeric-string> : list<numeric-string>) : ($array is non-empty-array ? non-empty-list<numeric-string> : list<string>))
*/
function keysAsString(array $array): array
{
	$keys = [];

	foreach ($array as $k => $_) {
		$keys[] = (string)$k;
	}

	return $keys;
}

function () {
	assertType('array<int, numeric-string>', keysAsString([]));
	assertType('non-empty-array<int, numeric-string>', keysAsString(['' => '']));
};
