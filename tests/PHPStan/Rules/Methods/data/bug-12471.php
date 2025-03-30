<?php

namespace Bug12471;

trait T
{
	public function f():void {}
}

class A {
	public function f():void {}
}

class B {
	use T;
}

class C extends A {
	use T;
}

/** @phpstan-require-extends D */
trait TT
{
	public function f():void {}
}

class D {}

class AA extends D {
	public function f():void {}
}

class BB extends D {
	use TT;
}

class CC extends AA {
	use TT;
}
