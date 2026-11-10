<?php declare(strict_types = 1);

namespace WalkTraceClosureBindNamedArguments;

// Closure::bind() with named arguments in any order: the scope factory reads
// $newThis and $newScope off the normalized call, so the bound closure's
// $this and class scope must not depend on the order the arguments are
// written in
class Foo
{

	private int $x = 1;

	private static string $s = 'a';

}

function f(Foo $foo): void
{
	$a = \Closure::bind(newThis: $foo, closure: function () {
		return $this->x;
	});
	$b = \Closure::bind(newThis: $foo, newScope: Foo::class, closure: function () {
		return [$this->x, Foo::$s];
	});
	$c = \Closure::bind(newScope: Foo::class, closure: function () {
		return $this;
	}, newThis: $foo);
	$d = \Closure::bind(function () {
		return $this->x;
	}, newScope: Foo::class, newThis: $foo);
	$e = \Closure::bind(closure: fn () => $this->x, newScope: Foo::class, newThis: $foo);
}
