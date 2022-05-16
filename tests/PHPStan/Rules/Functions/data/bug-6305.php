<?php declare(strict_types = 1);

namespace Bug6305;

class A {}

class B extends A {}

$b = new B();

if (is_subclass_of(A::class, A::class, false)) { // should error
}

if (is_subclass_of(B::class, B::class, false)) { // should error
}

if (is_subclass_of(B::class, A::class, false)) { // should error
}

if (is_subclass_of($b, A::class)) { // fine
}

if (is_subclass_of($b, B::class)) { // fine
}
