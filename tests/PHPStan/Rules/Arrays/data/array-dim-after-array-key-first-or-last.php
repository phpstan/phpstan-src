<?php declare(strict_types = 1);

namespace ArrayDimAfterArrayKeyFirstOrLast;

class HelloWorld
{
	/**
	 * @param list<string> $hellos
	 */
	public function last(array $hellos): string
	{
		if ($hellos !== []) {
			$lastHelloKey = array_key_last($hellos);
			return $hellos[$lastHelloKey];
		} else {
			$lastHelloKey = array_key_last($hellos);
			return $hellos[$lastHelloKey];
		}
	}

	/**
	 * @param list<string> $hellos
	 */
	public function first(array $hellos): string
	{
		if ($hellos !== []) {
			$firstHelloKey = array_key_first($hellos);
			return $hellos[$firstHelloKey];
		}

		return 'nothing';
	}

	/**
	 * @param array{first: int, middle: float, last: bool} $hellos
	 */
	public function shape(array $hellos): int|bool
	{
		$firstHelloKey = array_key_first($hellos);
		$lastHelloKey = array_key_last($hellos);

		if (rand(0,1)) {
			return $hellos[$firstHelloKey];
		}
		return $hellos[$lastHelloKey];
	}
}
