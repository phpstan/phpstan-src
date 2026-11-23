<?php // lint >= 8.1

namespace ArrayMapUnpackedArgsFirstClassCallable;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param T $value
 * @return T
 */
function identity($value)
{
	return $value;
}

/**
 * @param list<list<int>> $lists
 * @param array{0: array{1}, 1?: array{2, 3}} $shorterFirst
 */
function genericCallbackWithUnknownCount(array $lists, array $shorterFirst): void
{
	assertType('list<int>', array_map(identity(...), ...$lists));
	assertType('list<1|null>', array_map(identity(...), ...$shorterFirst));
}
