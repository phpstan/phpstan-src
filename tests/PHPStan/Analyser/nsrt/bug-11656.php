<?php

declare(strict_types = 1);

namespace Bug11656;

use function PHPStan\Testing\assertType;

class Test
{
	/**
	 * @param array{string[], string} $data
	 */
	public function test(mixed $data): string
	{
		$data = array_map(static fn ($value) => $value, $data);

		assertType("array{array<string>, string}", $data);

		return $data[1];
	}
}
