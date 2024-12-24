<?php declare(strict_types = 1);

namespace Bug3631;

/**
 * @return array<string>
 */
function someFunc(bool $flag): array
{
	$ids = [
		['fa', 'foo', 'baz']
	];

	if ($flag) {
		$ids[] = ['foo', 'bar', 'baz'];

	}

	if (count($ids) > 1) {
		return array_intersect(...$ids);
	}

	return $ids[0];
}

var_dump(someFunc(true));
var_dump(someFunc(false));
