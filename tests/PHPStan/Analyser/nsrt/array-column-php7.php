<?php // lint < 8.0

namespace ArrayColumn\Php7;

use function PHPStan\Testing\assertType;

class ArrayColumnPhp7Test
{

	/** @param array<int, array{column: string, key: array}> $array */
	public function testConstantArray1(array $array): void
	{
		assertType('array<int, string>', array_column($array, 'column', 'key'));
	}

	/** @param array<int, array{column: string, key: array|string}> $array */
	public function testConstantArray2(array $array): void
	{
		assertType('array<int|string, string>', array_column($array, 'column', 'key'));
	}

}
