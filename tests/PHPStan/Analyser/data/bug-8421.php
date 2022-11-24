<?php declare(strict_types = 1);

namespace Bug8421;

use function PHPStan\Testing\assertType;

final class Foo {
	/** @param-out int $i */
	final public function foo(mixed &$i): void {
		$i = 5;
	}
	final public function bar(): void {
		$this->foo($a);
		assertType('int', $a);
	}
}
