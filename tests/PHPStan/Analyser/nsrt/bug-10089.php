<?php

namespace Bug10089;

use function PHPStan\Testing\assertType;

class Test
{

	protected function create_matrix(int $size): array
	{
		$size = min(8, $size);
		$matrix = [];
		for ($i = 0; $i < $size; $i++) {
			$matrix[] = array_fill(0, $size, 0);
		}

		assertType('list<non-empty-list<0>>', $matrix);

		$matrix[$size - 1][8] = 3;

		assertType('non-empty-array<int, non-empty-array<int<0, max>, 0|3>>', $matrix);

		return $matrix;
	}

}


