<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug14328;

class Foo {
	public function returnThis(mixed $value): self {
		return $this;
	}
}

class Bar {
	public static function doSomething(mixed $value): void {
	}
}

function testMethodCall(): void {
	$callback = fn (): never => throw new \Exception();
	$x = (new Foo())->returnThis($callback())->returnThis('x');
	$y = 'this will never run';
}

function testStaticCall(): void {
	$callback = fn (): never => throw new \Exception();
	Bar::doSomething($callback());
	$b = 'this will never run';
}
