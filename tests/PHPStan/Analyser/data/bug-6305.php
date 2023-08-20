<?php declare(strict_types = 1);

namespace Bug6305Types;

use function PHPStan\Testing\assertType;

class A {}

class B extends A {}

$b = new B();

if (is_subclass_of($b, A::class)) {
	assertType('Bug6305Types\B', $b);
}

if (is_subclass_of($b, B::class)) {
	assertType('*NEVER*', $b);
}
