<?php

namespace VersionComparePhpVersionScope;

use function PHPStan\Testing\assertType;

// 3-arg form: version_compare(PHP_VERSION, '8.0', '>=')
if (version_compare(PHP_VERSION, '8.0', '>=')) {
	assertType('int<80000, 80599>', PHP_VERSION_ID);
} else {
	assertType('int<50207, 79999>', PHP_VERSION_ID);
}

// 3-arg form: version_compare(PHP_VERSION, '8.0', '<')
if (version_compare(PHP_VERSION, '8.0', '<')) {
	assertType('int<50207, 79999>', PHP_VERSION_ID);
} else {
	assertType('int<80000, 80599>', PHP_VERSION_ID);
}

// 3-arg form: version_compare(PHP_VERSION, '8.4', '>'))
if (version_compare(PHP_VERSION, '8.4', '>')) {
	assertType('int<80401, 80599>', PHP_VERSION_ID);
} else {
	assertType('int<50207, 80400>', PHP_VERSION_ID);
}

// 3-arg form: version_compare(PHP_VERSION, '8.4', '<='))
if (version_compare(PHP_VERSION, '8.4', '<=')) {
	assertType('int<50207, 80400>', PHP_VERSION_ID);
} else {
	assertType('int<80401, 80599>', PHP_VERSION_ID);
}

// 3-arg form: swapped arguments - version_compare('8.0', PHP_VERSION, '<=')
if (version_compare('8.0', PHP_VERSION, '<=')) {
	assertType('int<80000, 80599>', PHP_VERSION_ID);
} else {
	assertType('int<50207, 79999>', PHP_VERSION_ID);
}

// 3-arg form: with patch version - version_compare(PHP_VERSION, '8.0.1', '>=')
if (version_compare(PHP_VERSION, '8.0.1', '>=')) {
	assertType('int<80001, 80599>', PHP_VERSION_ID);
} else {
	assertType('int<50207, 80000>', PHP_VERSION_ID);
}

// 2-arg form: version_compare(PHP_VERSION, '8.0') === 1
if (version_compare(PHP_VERSION, '8.0') === 1) {
	assertType('int<80001, 80599>', PHP_VERSION_ID);
} else {
	assertType('int<50207, 80000>', PHP_VERSION_ID);
}

// 2-arg form: version_compare(PHP_VERSION, '8.0') >= 0
if (version_compare(PHP_VERSION, '8.0') >= 0) {
	assertType('int<80000, 80599>', PHP_VERSION_ID);
} else {
	assertType('int<50207, 79999>', PHP_VERSION_ID);
}

// 2-arg form: version_compare(PHP_VERSION, '8.0') === -1
if (version_compare(PHP_VERSION, '8.0') === -1) {
	assertType('int<50207, 79999>', PHP_VERSION_ID);
} else {
	assertType('int<80000, 80599>', PHP_VERSION_ID);
}

// 2-arg form: version_compare(PHP_VERSION, '8.0') < 0
if (version_compare(PHP_VERSION, '8.0') < 0) {
	assertType('int<50207, 79999>', PHP_VERSION_ID);
} else {
	assertType('int<80000, 80599>', PHP_VERSION_ID);
}

// 3-arg form with operator alias: version_compare(PHP_VERSION, '8.0', 'ge')
if (version_compare(PHP_VERSION, '8.0', 'ge')) {
	assertType('int<80000, 80599>', PHP_VERSION_ID);
} else {
	assertType('int<50207, 79999>', PHP_VERSION_ID);
}

// 3-arg form with operator alias: version_compare(PHP_VERSION, '8.0', 'lt')
if (version_compare(PHP_VERSION, '8.0', 'lt')) {
	assertType('int<50207, 79999>', PHP_VERSION_ID);
} else {
	assertType('int<80000, 80599>', PHP_VERSION_ID);
}

// 3-arg form: equality - version_compare(PHP_VERSION, '8.0', '==')
if (version_compare(PHP_VERSION, '8.0', '==')) {
	assertType('80000', PHP_VERSION_ID);
} else {
	assertType('int<50207, 79999>|int<80001, 80599>', PHP_VERSION_ID);
}

// 2-arg form: version_compare(PHP_VERSION, '8.0') === 0
if (version_compare(PHP_VERSION, '8.0') === 0) {
	assertType('80000', PHP_VERSION_ID);
} else {
	assertType('int<50207, 79999>|int<80001, 80599>', PHP_VERSION_ID);
}

// 2-arg form with swapped args: version_compare('8.0', PHP_VERSION) === -1
if (version_compare('8.0', PHP_VERSION) === -1) {
	assertType('int<80001, 80599>', PHP_VERSION_ID);
} else {
	assertType('int<50207, 80000>', PHP_VERSION_ID);
}

// 2-arg form: version_compare(PHP_VERSION, '8.0') > 0
if (version_compare(PHP_VERSION, '8.0') > 0) {
	assertType('int<80001, 80599>', PHP_VERSION_ID);
} else {
	assertType('int<50207, 80000>', PHP_VERSION_ID);
}

// 2-arg form: version_compare(PHP_VERSION, '8.0') <= 0
if (version_compare(PHP_VERSION, '8.0') <= 0) {
	assertType('int<50207, 80000>', PHP_VERSION_ID);
} else {
	assertType('int<80001, 80599>', PHP_VERSION_ID);
}

// 3-arg form with major-only version: version_compare(PHP_VERSION, '8', '>=')
if (version_compare(PHP_VERSION, '8', '>=')) {
	assertType('int<80000, 80599>', PHP_VERSION_ID);
} else {
	assertType('int<50207, 79999>', PHP_VERSION_ID);
}

// Playground sample: variadic parameter type depends on PHP version
if (version_compare(PHP_VERSION, '8.0', '>=')) {
	class FooVersionCompare8 {
		/**
		 * @param mixed $x
		 */
		public function doBaz(...$x): void {
			assertType('array<int|string, mixed>', $x);
		}
	}
} else {
	class FooVersionCompare9 {
		/**
		 * @param mixed $x
		 */
		public function doBaz(...$x): void {
			assertType('list<mixed>', $x);
		}
	}
}
