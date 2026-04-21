<?php

namespace Bug10089;


use function PHPStan\Testing\assertType;

class Test
{

	protected function create_matrix(int $size): array
	{
		$size = min(8, $size);
		assertType('int<min, 8>', $size);
		$matrix = [];
		for ($i = 0; $i < $size; $i++) {
			assertType('int<0, 7>', $i);
			$matrix[] = array_fill(0, $size, 0);
		}

		// array<int<0, max>, non-empty-array<int, 0>>
		assertType('list<non-empty-list<0>>', $matrix);

		$matrix[$size - 1][8] = 3;

		// non-empty-array<int, non-empty-array<int, 0|3>&hasOffsetValue(8, 3)>
		assertType('non-empty-list<(non-empty-array<int<0, max>, 0|3>&hasOffsetValue(8, 3))|non-empty-list<0>>', $matrix);

		for ($i = 0; $i <= $size; $i++) {
			if ($matrix[$i][8] === 0) {
				// ...
			}
			if ($matrix[8][$i] === 0) {
				// ...
			}
			if ($matrix[$size - 1 - $i][8] === 0) {
				// ...
			}
		}

		return $matrix;
	}

}


