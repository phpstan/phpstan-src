<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug14328;

class Foo {
	public function returnThis(mixed $value): self {
		return $this;
	}

	public static function returnSelf(mixed $value): self {
		return new self();
	}
}

function testMethodCallChainedWithMethodCall(): void {
	$callback = fn (): never => throw new \Exception();
	$x = (new Foo())->returnThis($callback())->returnThis('x');
	$y = 'this will never run';
}

function testMethodCallChainedWithStaticCall(): void {
	$callback = fn (): never => throw new \Exception();
	$x = (new Foo())->returnThis($callback())::returnSelf('x');
	$y = 'this will never run';
}

function testStaticCallChainedWithMethodCall(): void {
	$callback = fn (): never => throw new \Exception();
	$a = Foo::returnSelf($callback())->returnThis('x');
	$b = 'this will never run either';
}

function testStaticCallChainedWithStaticCall(): void {
	$callback = fn (): never => throw new \Exception();
	$a = Foo::returnSelf($callback())::returnSelf('x');
	$b = 'this will never run either';
}
