<?php // lint < 8.0

declare(strict_types = 1);

namespace ArrayReversePhp7;

use function PHPStan\Testing\assertType;

class Foo
{
	public function notArray(bool $bool): void
	{
		assertType('null', array_reverse($bool));
		assertType('null', array_reverse($bool, true));
	}
}
