<?php // lint > 7.4

namespace Bug13904;

use function PHPStan\Testing\assertType;

if (version_compare( PHP_VERSION, '8.0', '>=' )) {
	class Foo8 {
		/**
		 * @param mixed $x
		 */
		public function doBaz(...$x): void {
			assertType('array<int|string, mixed>', $x);
		}
	}
} else {
	class Foo9 {
		/**
		 * @param mixed $x
		 */
		public function doBaz(...$x): void {
			assertType('list<mixed>', $x);
		}
	}
}
