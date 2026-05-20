<?php declare(strict_types = 1);

namespace Bug14661;

class A
{
	public function mixedOrder(
		?string $other = null,
		?string $target = null,
	): void {}

	public function sameOrder(
		?string $other = null,
		?string $target = null,
	): void {}
}

class B
{
	public function mixedOrder(
		?string $target = null,
		?string $other = null,
	): void {}

	public function sameOrder(
		?string $other = null,
		?string $target = null,
	): void {}
}

function mixedOrder(A|B $obj): void
{
	$obj->mixedOrder(target: 'value');
	$obj->mixedOrder(other: 'value');
	$obj->mixedOrder(target: 'value1', other: 'value2');
	$obj->mixedOrder(other: 'value1', target: 'value2');
}

function sameOrder(A|B $obj): void
{
	$obj->sameOrder(target: 'value');
	$obj->sameOrder(other: 'value');
}

function unknownParam(A|B $obj): void
{
	$obj->mixedOrder(unknown: 'value');
}

class C
{
	public function foo(string $a, int $b): void {}
}

class D
{
	public function foo(int $b, string $a): void {}
}

function differentTypes(C|D $obj): void
{
	$obj->foo(a: 'hello', b: 42);
	$obj->foo(b: 42, a: 'hello');
	$obj->foo(a: 'hello');
	$obj->foo(b: 42);
}

class E
{
	public static function bar(
		?string $x = null,
		?string $y = null,
	): void {}
}

class F
{
	public static function bar(
		?string $y = null,
		?string $x = null,
	): void {}
}

function staticMethodCall(E|F $obj): void
{
	$obj::bar(x: 'value');
	$obj::bar(y: 'value');
	$obj::bar(x: 'v1', y: 'v2');
}
