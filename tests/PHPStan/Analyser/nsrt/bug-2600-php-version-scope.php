<?php // lint > 7.4

namespace Bug2600PhpVersionScope;

use function PHPStan\Testing\assertType;

if (PHP_VERSION_ID >= 80000) {
	class Foo8 {
		/**
		 * @param mixed $x
		 */
		public function doBaz(...$x) {
			assertType('array<int|string, mixed>', $x);
		}
	}
} else {
	class Foo9 {
		/**
		 * @param mixed $x
		 */
		public function doBaz(...$x) {
			assertType('list<mixed>', $x);
		}
	}

}
