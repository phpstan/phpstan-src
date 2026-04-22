<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug10729Types;

use function PHPStan\Testing\assertType;

class Foo
{
	public function bar(string $a, string $b): string
	{
		return $a . $b;
	}
}

function nullable(?Foo $foo): void
{
	$foo?->bar($a = 'hello', $b = 'world');
	assertType("'hello'|null", $a ?? null);
	assertType("'world'|null", $b ?? null);
}

function nonNullable(Foo $foo): void
{
	$foo->bar($a = 'hello', $b = 'world');
	assertType("'hello'", $a);
	assertType("'world'", $b);
}

function alwaysNull(): void
{
	$foo = null;
	$foo?->bar($a = 'hello', $b = 'world');
	assertType('null', $a ?? null); // $a is never assigned when $foo is always null
}

function chainedNullsafe(?Foo $foo): void
{
	$result = $foo?->bar($x = 'a', $y = 'b');
	assertType('string|null', $result);
	assertType("'a'|null", $x ?? null);
	assertType("'b'|null", $y ?? null);
}
