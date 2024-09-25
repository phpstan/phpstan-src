<?php declare(strict_types = 1);

namespace Bug11732;

use function PHPStan\Testing\assertType;

class Foo {
	public function testPHP8(): void
	{
		assertType('true', '' < 0);
		assertType('true', '' <= 0);

		assertType('false', 'foo' < 0);
		assertType('false', 'foo' <= 0);

		assertType('true', '' < 0.0);
		assertType('true', '' <= 0.0);

		assertType('false', 'foo' < 0.0);
		assertType('false', 'foo' <= 0.0);

		assertType('true', '' < 1);
		assertType('true', '' <= 1);

		assertType('false', 'foo' < 1);
		assertType('false', 'foo' <= 1);

		assertType('true', '' < 1.0);
		assertType('true', '' <= 1.0);

		assertType('false', 'foo' < 1.0);
		assertType('false', 'foo' <= 1.0);
	}
}
