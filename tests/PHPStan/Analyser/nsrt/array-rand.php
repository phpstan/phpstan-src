<?php

declare(strict_types = 1);

namespace ArrayRandReturnType;

use function PHPStan\Testing\assertType;

/**
 * @param array{a: 1, b: 2, c: 3} $shape
 * @param non-empty-list<string> $list
 * @param non-empty-array<string, int> $strKeyed
 * @param int<2, max> $atLeastTwo
 * @param positive-int $positive
 */
function f(array $shape, array $list, array $strKeyed, int $atLeastTwo, int $positive, int $int): void
{
	assertType("'a'|'b'|'c'", array_rand($shape));
	assertType("'a'|'b'|'c'", array_rand($shape, 1));
	assertType("array{'a'|'b'|'c', 'a'|'b'|'c'}", array_rand($shape, 2));
	assertType("array{'a'|'b'|'c', 'a'|'b'|'c', 'a'|'b'|'c'}", array_rand($shape, 3));

	assertType('int<0, max>', array_rand($list));
	assertType('array{int<0, max>, int<0, max>}', array_rand($list, 2));

	// a decimal-integer string key comes back as an int, see #15073
	assertType('(int|string)', array_rand($strKeyed));
	assertType('array{(int|string), (int|string)}', array_rand($strKeyed, 2));

	// $num is known to be 2 or more, but not by how much
	assertType("non-empty-list<'a'|'b'|'c'>", array_rand($shape, $atLeastTwo));

	// $num may be 1, which gives back a single key instead of a list
	assertType("'a'|'b'|'c'|non-empty-list<'a'|'b'|'c'>", array_rand($shape, $positive));
	assertType("'a'|'b'|'c'|non-empty-list<'a'|'b'|'c'>", array_rand($shape, $int));

	// past KEY_COUNT_LIMIT the shape gives way to a list
	assertType('non-empty-list<(int|string)>', array_rand($strKeyed, 200));
}
