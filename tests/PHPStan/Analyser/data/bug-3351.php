<?php declare(strict_types = 1);

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(): void
	{
		$a = ['a', 'b', 'c'];
		$b = [1, 2, 3];

		$c = $this->combine($a, $b);
		assertType('array<string, int>|false', $c);

		assertType('array{a: 1, b: 2, c: 3}', array_combine($a, $b));
	}

	/**
	 * @template TKey
	 * @template TValue
	 * @param array<TKey> $keys
	 * @param array<TValue> $values
	 *
	 * @return array<TKey, TValue>|false
	 */
	private function combine(array $keys, array $values)
	{
		return array_combine($keys, $values);
	}
}
