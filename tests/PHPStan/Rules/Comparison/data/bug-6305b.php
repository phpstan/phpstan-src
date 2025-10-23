<?php

namespace Bug6305b;

class A {}

class B extends A {}

$b = mt_rand(0, 1) === 0 ? new B() : new A();

if (is_subclass_of($b, A::class)) {
	if (is_subclass_of($b, A::class)) {
		echo 'x';
	}
}

if (is_subclass_of($b, B::class)) {
	if (is_subclass_of($b, B::class)) {
		echo 'y';
	}
}

$b = mt_rand(0, 1) === 0 ? A::class : B::class;

if (is_subclass_of($b, A::class)) {
	if (is_subclass_of($b, A::class)) {
		echo 'x';
	}
}

if (is_subclass_of($b, B::class)) {
	if (is_subclass_of($b, B::class)) {
		echo 'y';
	}
}
