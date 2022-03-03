<?php

namespace TypeSpecifierIdentical;

use function PHPStan\Testing\assertType;

class Foo
{

	public function foo(\stdClass $a, \stdClass $b): void
	{
		if ($a === $a) {
			assertType('stdClass', $a);
		} else {
			assertType('*NEVER*', $a);
		}

		if ($b !== $b) {
			assertType('*NEVER*', $b);
		} else {
			assertType('stdClass', $b);
		}

		if ($a === $b) {
			assertType('stdClass', $a);
			assertType('stdClass', $b);
		} else {
			assertType('stdClass', $a);
			assertType('stdClass', $b);
		}

		if ($a !== $b) {
			assertType('stdClass', $a);
			assertType('stdClass', $b);
		} else {
			assertType('stdClass', $a);
			assertType('stdClass', $b);
		}

		assertType('stdClass', $a);
		assertType('stdClass', $b);
	}

}
