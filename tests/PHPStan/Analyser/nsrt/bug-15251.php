<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15251Nsrt;

use function PHPStan\Testing\assertType;

class A
{
	public function foo(int $a, int ...$rest): void {}
}

class B
{
	public function foo(int $a): void {}
}

class C
{
	public function foo(int ...$rest): void {}
}

class D
{
	public function foo(int $a, string $b, string $c): void {}
}

function test(A|B $ab, C|B $cb, C|D $cd, D|C $dc): void
{
	assertType('Closure(int, int ...): void', $ab->foo(...));
	assertType('Closure(int ...): void', $cb->foo(...));
	assertType('Closure(int|string ...): void', $cd->foo(...));
	assertType('Closure(int|string ...): void', $dc->foo(...));
}
