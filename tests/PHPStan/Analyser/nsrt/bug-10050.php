<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug10050;

use function PHPStan\Testing\assertType;

interface Value
{
	public function get(): int|string|null;
}

interface StringValue extends Value
{
	public function get(): string;
}

function testMethod(Value $v): void
{
	assertType('int|string|null', $v->get());
	if ($v->get() === null) {
		return;
	}
	assertType('int|string', $v->get());
	if ($v instanceof StringValue) {
		assertType('string', $v->get());
	}
	assertType('int|string', $v->get());
}

class A
{
	public int|null $p;
}

class B
{
	public string|null $p;
}

function testProperty(A|B $x): void
{
	assertType('int|string|null', $x->p);
	if ($x->p === null) {
		return;
	}
	assertType('int|string', $x->p);
	if ($x instanceof A) {
		assertType('int', $x->p);
	}
	assertType('int|string', $x->p);
}
