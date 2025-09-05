<?php

namespace Bug6989;

use function PHPStan\Testing\assertType;

class MyClass
{
	public const MY_KEY = 'key';

	/**
	 * @param array{static::MY_KEY: string} $items
	 *
	 * @return string
	 */
	public function myMethod(array $items): array
	{
		assertType('array{key: string}', $items);

		return $items[static::MY_KEY];
	}
}
