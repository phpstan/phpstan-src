<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15251;

class A
{
	public function foo(int $a, int ...$rest): void {}

	public static function sfoo(int $a, int ...$rest): void {}
}

class B
{
	public function foo(int $a): void {}

	public static function sfoo(int $a): void {}
}

class C
{
	public function foo(int ...$rest): void {}

	public static function sfoo(int ...$rest): void {}
}

function test(A|B $ab, C|B $cb): void
{
	$cb->foo(1, 2, 3);
	$ab->foo(1, 2, 3);

	$closure = $ab->foo(...);
	$closure(1, 2, 3);
}

function testStatic(A|B $ab, C|B $cb): void
{
	$cb::sfoo(1, 2, 3);
	$ab::sfoo(1, 2, 3);

	$ab::sfoo();
}

function testCallable(A|B $ab): void
{
	$callable = [$ab, 'foo'];
	$callable(1, 2, 3);

	call_user_func([$ab, 'foo'], 1, 2, 3);
}

interface IA
{
	public function foo(int ...$rest): void;
}

interface IB
{
	public function foo(int $a): void;
}

function testIntersection(IA&IB $ab, IB&IA $ba): void
{
	$ab->foo(1, 2, 3);
	$ba->foo(1, 2, 3);
}

interface IInvokeA
{
	public function __invoke(int $a, int ...$rest): void;
}

interface IInvokeB
{
	public function __invoke(int $a): void;
}

function testInvoke(IInvokeA|IInvokeB $union, IInvokeA&IInvokeB $intersection): void
{
	$union(1, 2, 3);
	$intersection(1, 2, 3);
}

interface IRefA
{
	public function bar(int $a, int &...$rest): void;
}

interface IRefB
{
	public function bar(int $a): void;
}

function testByRefVariadic(IRefA|IRefB $ab): void
{
	$b = 2;
	$c = 3;
	$ab->bar(1, $b, $c);
}

interface IManyA
{
	public function foo(int ...$rest): void;
}

interface IManyB
{
	public function foo(int $a, string $b, string $c): void;
}

function testDroppedParameters(IManyA|IManyB $ab, IManyB|IManyA $ba): void
{
	$ab->foo(1, 'a', 'b', 'c');
	$ba->foo(1, 'a', 'b', 'c');
}
