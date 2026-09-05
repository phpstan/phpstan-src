<?php declare(strict_types = 1);

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
			assertType('string', $key);
			echo $data[$key];
		}
		echo $key === null ? null : $data[$key];
	}
}
