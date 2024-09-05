<?php // lint >= 8.0

declare(strict_types = 1);

namespace ArrayReversePhp8;

use function PHPStan\Testing\assertType;

class Foo
{
	public function notArray(bool $bool): void
	{
		assertType('*NEVER*', array_reverse($bool));
		assertType('*NEVER*', array_reverse($bool, true));
	}
}
