<?php declare(strict_types = 1);

namespace Bug11129b;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/** @param array<string> $array */
	public function sayHello(array $array): void
	{
		$pos = 0;
		foreach ($array as $element) {
			++$pos;

			if ($pos < 10) {
				$pos = '0' . $pos;
			}
		}
		assertType('0|float|(non-falsy-string&uppercase-string)', $pos);
	}

	/** @param array<string> $array */
	public function withPlusOne(array $array): void
	{
		$pos = 0;
		foreach ($array as $element) {
			$pos = $pos + 1;

			if ($pos < 10) {
				$pos = '0' . $pos;
			}
		}
		assertType('0|float|(non-falsy-string&uppercase-string)', $pos);
	}
}
