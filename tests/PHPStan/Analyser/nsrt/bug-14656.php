<?php declare(strict_types = 1);

namespace Bug14656;

use function PHPStan\Testing\assertType;

class ArrayFlipUnionValues
{
	/** @param array{0: 'a'|'b'|'c', 1: 'a'|'b'|'c', 2: 'a'|'b'|'c'} $a */
	public function allUnion(array $a): void
	{
		assertType("non-empty-array{a?: 0|1|2, b?: 0|1|2, c?: 0|1|2}", array_flip($a));
	}

	/** @param array{0: 'a'|'b', 1: 'b'|'c'} $a */
	public function overlappingUnion(array $a): void
	{
		assertType("non-empty-array{a?: 0, b?: 0|1, c?: 1}", array_flip($a));
	}

	/** @param array{0: 'a'|'b', 1: 'c'} $a */
	public function mixedUnionAndConstant(array $a): void
	{
		assertType("array{a?: 0, b?: 0, c: 1}", array_flip($a));
	}
}

class ArrayFillKeysUnionValues
{
	/** @param array{0: 'a'|'b', 1: 'b'|'c'} $a */
	public function overlappingUnion(array $a): void
	{
		assertType("non-empty-array{a?: 'x', b?: 'x', c?: 'x'}", array_fill_keys($a, 'x'));
	}

	/** @param array{0: 'a'|'b'|'c', 1: 'a'|'b'|'c', 2: 'a'|'b'|'c'} $a */
	public function allUnion(array $a): void
	{
		assertType("non-empty-array{a?: 'x', b?: 'x', c?: 'x'}", array_fill_keys($a, 'x'));
	}

	/** @param array{0: 'a'|'b', 1: 'c'} $a */
	public function mixedUnionAndConstant(array $a): void
	{
		assertType("array{a?: 'x', b?: 'x', c: 'x'}", array_fill_keys($a, 'x'));
	}
}
