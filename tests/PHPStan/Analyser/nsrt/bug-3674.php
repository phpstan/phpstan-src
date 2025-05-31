<?php declare(strict_types = 1);

namespace Bug3674;

use Iterator;
use function PHPStan\Testing\assertType;

/**
 * @param Iterator<int> $it
 */
function foo(Iterator $it): void {
	assertType('int|null', $it->current());

	if ($it->valid()) {
		assertType('int', $it->current());

		$it->rewind();

		assertType('int|null', $it->current());

		if ($it->valid()) {
			assertType('int', $it->current());
		} else {
			assertType('null', $it->current());
		}
	} else {
		assertType('null', $it->current());
	}
}

/**
 * @param Iterator<int> $it
 */
function bar(Iterator $it): void {
	assertType('bool', $it->valid());
	assertType('int|null', $it->current());

	foreach ($it as $v) {
		assertType('true', $it->valid());
		assertType('int', $it->current());
	}

	assertType('false', $it->valid());
	assertType('null', $it->current());
}
