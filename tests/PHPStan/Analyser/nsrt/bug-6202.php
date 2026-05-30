<?php declare(strict_types = 1);

namespace Bug6202;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	/**
	 * @param array<string, mixed> $array
	 */
	public function sayHello(array $array): void
	{
		if (isset($array['mightExist']) && !is_string($array['mightExist'])) {
			throw new \Exception('Has to be string if set');
		}
		assertType('string', $array['mightExist'] ?? '');
	}

}
