<?php declare(strict_types = 1);

namespace ObjectAliasPropertyWrite;

use function PHPStan\Testing\assertType;

class C
{

	public ?int $v = null;

	public ?C $next = null;

	public function reset(): void
	{
		$this->v = null;
	}

}

function viaAlias(C $a): void
{
	$a->v = 1;
	$c = $a;
	$c->v = null;
	assertType('null', $a->v);
}

function viaOriginal(C $a): void
{
	$c = $a;
	$c->v = 1;
	$a->v = null;
	assertType('null', $c->v);
}

function aliasBroken(C $a): void
{
	$a->v = 1;
	$c = $a;
	$c = new C();
	$c->v = null;
	assertType('1', $a->v);
}

function originalReassigned(C $a): void
{
	$c = $a;
	$a = new C();
	$a->v = 1;
	$c->v = null;
	assertType('1', $a->v);
}

function cloned(C $a): void
{
	$a->v = 1;
	$c = clone $a;
	$c->v = null;
	assertType('1', $a->v);
}

function fromProperty(C $a): void
{
	$a->next = new C();
	$a->next->v = 1;
	$c = $a->next;
	$c->v = null;
	assertType('null', $a->next->v);
}

function methodCallOnAlias(C $a): void
{
	$a->v = 1;
	$c = $a;
	$c->reset();
	assertType('int|null', $a->v);
}

function maybeAlias(C $a, bool $x): void
{
	$a->v = 1;
	$c = $x ? $a : new C();
	$c->v = null;
	assertType('int|null', $a->v);

	$a->v = 1;
	if ($x) {
		$d = $a;
	} else {
		$d = new C();
	}
	$d->v = 5;
	assertType('int|null', $a->v);
}

function middleOfChainReassigned(C $a): void
{
	$a->v = 1;
	$b = $a;
	$c = $b;
	$b = new C();
	$c->v = null;
	assertType('null', $a->v);
}

function passedToFunction(C $a): void
{
	$a->v = 1;
	$c = $a;
	resetC($c);
	assertType('int|null', $a->v);
}

function resetC(C $c): void
{
	$c->v = null;
}

function capturedByClosure(C $a): void
{
	$a->v = 1;
	$c = $a;
	$f = function () use ($c): void {
		$c->v = null;
	};
	$f();
	assertType('int|null', $a->v);
}

function propertyReassignedAfterAlias(C $a, C $b): void
{
	$a->next = $b;
	$c = $a->next;
	$a->next = new C();
	$a->next->v = 3;
	$c->v = 4;
	assertType('3', $a->next->v);
}

class D
{

	public ?int $v = null;

	public function viaThis(): void
	{
		$this->v = 1;
		$c = $this;
		$c->v = null;
		assertType('null', $this->v);
	}

}

function identity(C $a, ?C $b, bool $x, float $f, C $other): void
{
	$c = $a;
	assertType('true', $a === $c);
	assertType('false', $a !== $c);
	assertType('true', $c === $a);
	assertType('bool', $a === $other);

	$d = $c;
	$c = new C();
	assertType('true', $a === $d);
	assertType('bool', $a === $c);

	$e = $b;
	assertType('true', $b === $e);

	$g = $x ? $a : $other;
	assertType('bool', $a === $g);

	$h = $f;
	assertType('bool', $f === $h);

	$a->next = $other;
	$n = $a->next;
	// a call not known to write it can replace a property, so only variables are known to be identical
	assertType('bool', $n === $a->next);
	assertType('bool', $n === $d->next);
	$a->next = null;
	assertType('false', $n === $a->next);

	$a->reset();
	assertType('true', $a === $d);
}

final class Holder
{

	public C $c;

	private static function replaceInGenerator(C $previous, self $holder): \Generator
	{
		$holder->c = new C();
		yield 1;
	}

	public static function replacedByGenerator(self $holder): \Generator
	{
		yield from self::replaceInGenerator($previous = $holder->c, $holder);
		assertType('bool', $holder->c !== $previous);
	}

	public static function replacedByIteratedGenerator(self $holder): void
	{
		$previous = $holder->c;
		foreach (self::replaceInGenerator($previous, $holder) as $_) {
		}
		assertType('bool', $holder->c === $previous);
	}

}
