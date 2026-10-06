<?php declare(strict_types = 1);

namespace WalkTraceClosureBindScopeNamedArguments;

// Closure::bind() with named arguments in any order: the scope factory reads
// $newThis and $newScope off the normalized call, so the bound closure's
// $this and class scope must not depend on the order the arguments are written in
class Foo
{
	private int $x = 1;
	private static string $s = 'a';
}

class Bar
{
}

function f(): void
{
	$fn = \Closure::bind(newThis: new Foo(), newScope: Foo::class, closure: function () {
		$a = $this->x;
		$b = self::$s;
		return $a;
	});
	$gn = \Closure::bind(newScope: Bar::class, closure: function () {
		return $this;
	}, newThis: new Foo());
}
