<?php declare(strict_types = 1);

namespace Bug10861;

class HelloWorld
{
	/**
	 *
	 * @param array<string,mixed>           $array1
	 * @param-out array<string,mixed>       $array1
	 */
	public function sayHello(array &$array1): void
	{
		$values_1 = $array1;

		$values_1 = array_filter($values_1, function (mixed $value): bool {
			return $value !== [];
		});

		/** @var array<string,mixed> $values_1 */
		$array1 = $values_1;
	}
}
