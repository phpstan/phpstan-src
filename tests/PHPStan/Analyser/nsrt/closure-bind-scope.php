<?php declare(strict_types = 1);

namespace ClosureBindScope;

use Closure;
use function PHPStan\Testing\assertType;

class Foo
{

	protected const A = 'Foo';

}

final class Bar extends Foo
{

	protected const A = 'Bar';

}

// Explicit class names resolve regardless of the bound scope.
assertType("'Foo'", Closure::bind(static fn () => Foo::A, null, Foo::class)());
assertType("'Foo'", Closure::bind(static fn () => Foo::A, null, Bar::class)());
assertType("'Bar'", Closure::bind(static fn () => Bar::A, null, Bar::class)());

// `self` / `parent` resolve against the Closure::bind() scope (3rd argument).
assertType("'Foo'", Closure::bind(static fn () => self::A, null, Foo::class)());
assertType("'Bar'", Closure::bind(static fn () => self::A, null, Bar::class)());
assertType("'Foo'", Closure::bind(static fn () => parent::A, null, Bar::class)());

class Container
{

	public function doBindFromInsideClass(): void
	{
		// Even when Closure::bind() is called from inside another class, the bound
		// scope wins over the enclosing class.
		assertType("'Foo'", Closure::bind(static fn () => self::A, null, Foo::class)());
		assertType("'Bar'", Closure::bind(static fn () => self::A, null, Bar::class)());
	}

}
