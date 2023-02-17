<?php

namespace Bug8888;

trait SomeTrait
{
	public function test(): void
	{
		if (method_exists($this, 'someTest')) {
			if ($this instanceof A) {
				return;
			}
			$this->someTest('foo');
		}
		return;
	}
}

class A
{
	use SomeTrait;

	public function someTest(string $foo, string $bar): void {}
}

class B
{
	use SomeTrait;

	public function someTest(string $foo): void {}
}

class Test
{
	public function test(): void
	{
		$a = new A();
		$a->test();
		$b = new B();
		$b->test();
	}
}
