<?php

namespace Bug6370;

interface I
{
}
class A implements I
{
	public function __construct(string $something)
	{
		// one class needs a constructor to reproduce the issue
		unset($something);
	}
}
class B implements I
{
}

class Foo
{

	function test(string $className): I
	{
		if (!in_array($className, [B::class, A::class], true)) {
			throw new \Exception();
		}
		switch($className) {
			case A::class:
				return new $className('something');
			case B::class:
				return new $className();
			default:
				throw new \Exception();
		}
	}

	function test2(string $className): I
	{
		if (!in_array($className, [B::class, A::class], true)) {
			throw new \Exception();
		}
		switch($className) {
			case A::class:
				return new $className(1);
			case B::class:
				return new $className();
			default:
				throw new \Exception();
		}
	}

}
