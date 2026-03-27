<?php declare(strict_types = 1);

namespace Bug10353;

trait Foo
{
	public function test(): string
	{
		if ($this instanceof HelloWorld) {
			return 'hello world';
		}
		if ($this instanceof OtherClass) {
			return 'other class';
		}
		throw new \Error();
	}
}

class HelloWorld
{
	use Foo;

	function bar(): string
	{
		return $this->test();
	}
}

class OtherClass
{
	use Foo;

	function bar(): string
	{
		return $this->test();
	}
}
