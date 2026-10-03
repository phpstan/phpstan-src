<?php

namespace NonEmptyShapeWithoutKeyZeroIsNotAList;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param non-empty-array{a?: string, b?: int} $strings
	 * @param non-empty-array{1?: string, 2?: int} $gap
	 * @param non-empty-array{0?: string, a?: int} $zero
	 */
	public function phpDoc(array $strings, array $gap, array $zero): void
	{
		// every non-empty list has the key 0
		assertType('false', array_is_list($strings));
		assertType('false', array_is_list($gap));
		assertType('bool', array_is_list($zero));
	}

	/**
	 * @param non-empty-list<'a'|'b'|'c'> $cols
	 * @param 'a'|'b'|'c' $col
	 */
	public function loop(array $cols, string $col): void
	{
		$loop = [];
		foreach ($cols as $c) {
			$loop[$c] = '0';
		}
		assertType("non-empty-array{a?: '0', b?: '0', c?: '0'}", $loop);
		assertType('false', array_is_list($loop));

		$direct = [];
		$direct[$col] = '0';
		assertType("non-empty-array{a?: '0', b?: '0', c?: '0'}", $direct);
		assertType('false', array_is_list($direct));
	}

	/**
	 * @param 0|1 $int
	 * @param 1|2 $past
	 * @param 'a'|'b' $string
	 */
	public function unionKeyWrite(int $int, int $past, string $string): void
	{
		// [0 => 'x'] is a list
		$a = [];
		$a[$int] = 'x';
		assertType("non-empty-array{0?: 'x', 1?: 'x'}", $a);
		assertType('bool', array_is_list($a));

		// [0 => 'x', 1 => 'y'] is a list
		$b = ['x'];
		$b[$past] = 'y';
		assertType('bool', array_is_list($b));

		// the key 0 cannot be written, so it is not a list while it is non-empty
		$c = [];
		$c[$string] = 'x';
		assertType('false', array_is_list($c));

		// ...but it can be empty again
		array_pop($c);
		assertType("array{a?: 'x'}", $c);
		assertType('bool', array_is_list($c));
	}

	/**
	 * @param array{a?: string, b?: int} $a
	 */
	public function nonEmptyIsNotKeptOnTheShape(array $a): void
	{
		if ($a === []) {
			return;
		}

		assertType('false', array_is_list($a));
		unset($a['a'], $a['b']);
		$a[] = 'x';
		// [0 => 'x'] is a list, so this must not be false
		assertType("array{'x'}", $a);
		assertType('bool', array_is_list($a));
	}

}
