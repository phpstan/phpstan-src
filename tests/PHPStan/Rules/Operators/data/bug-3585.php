<?php declare(strict_types = 1);

namespace Bug3585;

class Foo
{
	public function doFoo(): void
	{
		$this = 1;
		$this = new self();
		$this .= 'foo';
		[$this] = [1];
	}

	public static function doBar(): void
	{
		$this = 1; // allowed in static context? Actually no, PHP still forbids it
	}
}

function baz(): void
{
	$this = 1; // PHP forbids this too
}

class Test {
	public function foobar():void {
		$this ??= 123;
	}
}
