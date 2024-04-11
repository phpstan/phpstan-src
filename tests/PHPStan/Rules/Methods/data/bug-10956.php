<?php declare(strict_types = 1);

namespace Bug10956;

class HelloWorld
{
	const DEFAULT_VALUE = [NAN]; // unique value, distinct from anything, but equal to itself

	/**
	 * @param int|self::DEFAULT_VALUE $value
	 */
	public function test (mixed $value = self::DEFAULT_VALUE) {
		if ($value === self::DEFAULT_VALUE) {
			echo 'default';
		} else {
			echo (int) $value;
		}
	}
}
