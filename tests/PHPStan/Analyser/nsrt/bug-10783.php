<?php declare(strict_types = 1);

namespace Bug10783;

use ArrayObject;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(): bool
	{
		$a = new ArrayObject([]);
		assertType('ArrayObject<*NEVER*, *NEVER*>', $a);
		$a[] = 'test';
		assertType('ArrayObject<(int|string), mixed>', $a);
		assertType('array<mixed>', $a->getArrayCopy());

		return empty($a->getArrayCopy());
	}
}
