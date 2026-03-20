<?php

declare(strict_types = 1);

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

		$matrix[$size - 1][8] = 3;

		assertType('non-empty-list<non-empty-array<int<0, max>, 0|3>>', $matrix);

		for ($i = 0; $i <= $size; $i++) {
			assertType('0|3', $matrix[$i][8]);
		}

		return $matrix;
	}

}
