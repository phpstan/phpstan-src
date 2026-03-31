<?php declare(strict_types = 1);

namespace Bug10172;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @template T of array{data: array<mixed>}
	 *
	 * @param T $a
	 *
	 * @return T
	 */
	public function foo(array $a): array
	{
		assertType('T of array{data: array<mixed>} (method Bug10172\HelloWorld::foo(), argument)', $a);

		foreach ($a['data'] as $i) {
		}

		assertType('T of array{data: array<mixed>} (method Bug10172\HelloWorld::foo(), argument)', $a);

		return $a;
	}
}
