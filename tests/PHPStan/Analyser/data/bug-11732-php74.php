<?php declare(strict_types = 1);

namespace Bug11732;

use function PHPStan\Testing\assertType;

class Foo {
	public function testPHP74(): void
	{
		assertType('false', '' < 0);
		assertType('true', '' <= 0);

		assertType('false', 'foo' < 0);
		assertType('true', 'foo' <= 0);

		assertType('false', '' < 0.0);
		assertType('true', '' <= 0.0);

		assertType('false', 'foo' < 0.0);
		assertType('true', 'foo' <= 0.0);

		assertType('true', '' < 1);
		assertType('true', '' <= 1);

		assertType('true', 'foo' < 1);
		assertType('true', 'foo' <= 1);

		assertType('true', '' < 1.0);
		assertType('true', '' <= 1.0);

		assertType('true', 'foo' < 1.0);
		assertType('true', 'foo' <= 1.0);
	}
}
