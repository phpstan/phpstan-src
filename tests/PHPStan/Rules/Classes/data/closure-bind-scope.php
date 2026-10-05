<?php declare(strict_types = 1);

namespace ClosureBindScopeClasses;

use Closure;

class Foo
{

	protected const A = 'Foo';

	private const P = 'p';

	protected function __construct()
	{
	}

}

final class Bar extends Foo
{

	public function __construct()
	{
		parent::__construct();
	}

}

class NoParent
{

	public const X = 1;

}

function (Foo $foo, Bar $bar): void {
	// the bound class's protected members, through self/parent/static
	Closure::bind(static fn () => [self::A, static::A, new self(), new static()], null, Foo::class);
	Closure::bind(static fn () => [parent::A, new parent()], null, Bar::class);

	// an object newScope binds its class
	Closure::bind(static fn () => [self::A, new self()], null, $foo);
	Closure::bind(static fn () => [parent::A, new parent()], null, $bar);

	// private members of Foo are not accessible from the Bar scope
	Closure::bind(static fn () => self::P, null, Bar::class);

	// the bound class has no parent
	Closure::bind(static fn () => [parent::X, new parent()], null, NoParent::class);

	// undefined constant of the bound class
	Closure::bind(static fn () => self::NOPE, null, Foo::class);
	Closure::bind(static fn () => static::NOPE, null, Foo::class);

	// without a bound class self/parent/static stay outside of class scope
	Closure::bind(static fn () => [self::A, new self(), parent::A, new parent(), static::A, new static()], null);
	Closure::bind(static fn () => [self::A, new self()], null, 'static');
};

class Container
{

	private const OWN = 1;

	public function run(): void
	{
		// the bound class wins over the enclosing class
		Closure::bind(static fn () => [self::A, new self(), parent::A, new parent()], null, Bar::class);
		Closure::bind(static fn () => self::OWN, null, Foo::class);
		// the arguments are evaluated in the enclosing class: self::class is Container
		Closure::bind(static fn () => [self::OWN, new self()], null, self::class);
		Closure::bind(static fn () => [self::OWN, new self()], null, static::class);
		// a parent-less bound class in a class context
		Closure::bind(static fn () => [parent::X, new parent()], null, NoParent::class);
	}

}
