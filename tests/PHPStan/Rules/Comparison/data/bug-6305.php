<?php declare(strict_types = 1);

namespace Bug6305\Comparison;

class A {}

class B extends A {}

$b = new B();

if (is_subclass_of($b, A::class)) {
}

if (is_subclass_of($b, B::class)) {
}
