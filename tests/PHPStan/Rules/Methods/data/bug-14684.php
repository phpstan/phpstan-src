<?php declare(strict_types = 1);

namespace Bug14684;

class X {
	static public function publicFoo():void {}

	final static private function privateFoo():void {}

	static protected function protectedFoo():void {}
}

final class SubX extends X {
	static private function privateFoo():void {}
}

/** @param class-string<X> $row */
function testClassStringFinalMethod(string $row): void
{
	if (method_exists($row, 'publicFoo')) {
		$row::publicFoo();
	}

	if (method_exists($row, 'privateFoo')) {
		$row::privateFoo();
	}

	if (method_exists($row, 'protectedFoo')) {
		$row::protectedFoo();
	}
}

/** @param class-string<SubX> $row */
function testClassStringFinalClass(string $row): void
{
	if (method_exists($row, 'publicFoo')) {
		$row::publicFoo();
	}

	if (method_exists($row, 'privateFoo')) {
		$row::privateFoo();
	}

	if (method_exists($row, 'protectedFoo')) {
		$row::protectedFoo();
	}
}

function testLiteralClassCall(): void
{
	if (method_exists(X::class, 'privateFoo')) {
		X::privateFoo();
	}

	if (method_exists(X::class, 'protectedFoo')) {
		X::protectedFoo();
	}

	if (method_exists(SubX::class, 'privateFoo')) {
		SubX::privateFoo();
	}
}
