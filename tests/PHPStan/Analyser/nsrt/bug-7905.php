<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug7905;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
     * @param array<string, string> $data
	 */
	public function sayHello(array|null $data): void
	{
		$key = $data === null ? null : array_key_first($data);
		if ($key !== null) {
			assertType('non-empty-array<string, string>', $data);
			// array_key_first() on a string-keyed array can hand back an int
			assertType('(int|string)', $key);
			echo $data[$key];
		}
		echo $key === null ? null : $data[$key];
	}
}
