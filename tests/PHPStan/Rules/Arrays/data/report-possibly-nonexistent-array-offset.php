<?php

namespace NonexistentOffset;

class Feature7553
{
	public function arrayWithPossiblyUndefinedArrayOffset(array $array)
	{
		return $array['foo'];
	}

	public function arrayAccessWithPossiblyUndefinedArrayOffset(\ArrayAccess $a): void
	{
		echo $a['test'];
	}

	public function constantArrayWithPossiblyUndefinedArrayOffset(string $s): void
	{
		$a = ['foo' => 1];
		echo $a[$s];
	}

	/**
	 * @param array{bool|float|int|string|null} $a
	 * @return void
	 */
	public function testConstantArray(array $a): void
	{
		echo $a[0];
	}

	/**
	 * @param array<int, bool> $a
	 * @return void
	 */
	public function testConstantArray2(array $a): void
	{
		if (isset($a[0])) {
			echo $a[0];
		}
	}

	/**
	 * @param array{0: '9', A: 'Z', a: 'z'} $a
	 * @param '0'|'A'|'a' $dim
	 */
	public function testDimUnion(array $a, string $dim): void
	{
		echo $a[$dim];
	}

	/**
	 * @param non-empty-list<string> $a
	 */
	public function nonEmpty(array $a): void
	{
		echo $a[0];
	}

}
