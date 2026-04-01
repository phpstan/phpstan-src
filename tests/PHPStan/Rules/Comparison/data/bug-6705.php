<?php declare(strict_types = 1);

namespace Bug6705;

class Foo
{

	/**
	 * @param mixed[] $foo
	 */
	public function originalExample(array $foo): void
	{
		$a = [];
		foreach ($foo as $e) {
			$a[] = rand(5, 15) > 10 ? 0 : 1;
		}

		if (\in_array(0, $a, true)) {
			return;
		}
	}

	/**
	 * @param non-empty-array<int, 'a'|'b'> $multiValueArr
	 * @param non-empty-array<int> $nonEmptyInts
	 * @param array<int, 0|1> $possiblyEmptyArr
	 * @param non-empty-array<int, 'a'> $singleValueArr
	 * @param array<string> $strings
	 */
	public function testCases(
		array $multiValueArr,
		array $nonEmptyInts,
		array $possiblyEmptyArr,
		array $singleValueArr,
		array $strings,
		int $i,
	): void
	{
		// Always true: non-empty array with single value type matching needle
		if (in_array('a', $singleValueArr, true)) {} // always true

		// Always false: incompatible types
		if (in_array('b', $singleValueArr, true)) {} // always false
		if (in_array($i, $strings, true)) {} // always false

		// Indeterminate: needle compatible with values but not guaranteed
		if (in_array('a', $multiValueArr, true)) {} // no error
		if (in_array(0, $possiblyEmptyArr, true)) {} // no error
		if (in_array(0, $nonEmptyInts, true)) {} // no error
	}

}
