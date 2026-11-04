<?php declare(strict_types = 1);

namespace ClosureBindScopeTypehints;

use Closure;

class Base
{

}

class Foo extends Base
{

}

class Bar extends Base
{

}

class Lone
{

}

/**
 * @param class-string<Foo>|class-string<Bar> $withAncestor
 * @param class-string<Foo>|class-string<Lone> $noAncestor
 * @param class-string $plain
 */
function doFoo(string $withAncestor, string $noAncestor, string $plain): void
{
	// bound to one class: self/parent are its own and its parent
	Closure::bind(static fn (self $x, parent $y): self => $x, null, Foo::class);
	Closure::bind(static function (self $x, parent $y): parent {
		return $y;
	}, null, Foo::class);

	// bound to a class that is not exactly one known class
	Closure::bind(static fn (self $x, parent $y): self => $x, null, $withAncestor);
	Closure::bind(static fn (self $x): parent => $x, null, $noAncestor);
	Closure::bind(static fn (self $x): parent => $x, null, $plain);
	Closure::bind(static function (self $x): parent {
		return $x;
	}, null, $plain);

	// bound to no class
	Closure::bind(static fn (self $x): self => $x, null, 'static');
	Closure::bind(static function (self $x): self {
		return $x;
	}, null);
}
