<?php

namespace Bug10043;

class A
{
	public function foo(): void {}
}

class B extends A
{
	final public function foo(): void {}
}

class C extends B
{
	public function foo(): void {}
}
