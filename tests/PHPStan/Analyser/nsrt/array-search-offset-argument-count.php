<?php declare(strict_types = 1);

namespace ArraySearchOffsetArgumentCount;

use function PHPStan\Testing\assertType;

function singleArgument(): void
{
	$list = [1, 2, 3];
	// the haystack is array_search()'s second argument, so a call without one cannot be the
	// list-preserving idiom - the call itself is reported as invalid
	$list[array_search($list)] = 4;
	assertType('array{1|4, 2|4, 3|4, ...<int<min, -1>|int<3, max>|string, 4>}', $list);
}

/**
 * @param array{int, list<int>} $args
 */
function unpackedArguments(array $args): void
{
	$list = [1, 2, 3];
	$list[array_search(...$args)] = 4;
	assertType('array{1|4, 2|4, 3|4, ...<int<min, -1>|int<3, max>|string, 4>}', $list);
}
