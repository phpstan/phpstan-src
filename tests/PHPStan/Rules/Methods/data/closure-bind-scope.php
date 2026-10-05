<?php // lint >= 8.1

declare(strict_types = 1);

namespace ClosureBindScopeMethods;

use Closure;

class Foo
{

	protected static function sm(): string
	{
		return 'Foo';
	}

	private static function psm(): string
	{
		return 'Foo';
	}

}

final class Bar extends Foo
{

}

class NoParent
{

	public static function s(): void
	{
	}

}

function (Foo $foo, Bar $bar): void {
	// the bound class's protected methods, through self/parent/static
	Closure::bind(static fn () => [self::sm(), static::sm(), self::sm(...), static::sm(...)], null, Foo::class);
	Closure::bind(static fn () => [parent::sm(), parent::sm(...)], null, Bar::class);

	// an object newScope binds its class
	Closure::bind(static fn () => [self::sm(), self::sm(...)], null, $foo);
	Closure::bind(static fn () => parent::sm(), null, $bar);

	// private methods of Foo are not accessible from the Bar scope
	Closure::bind(static fn () => [self::psm(), self::psm(...)], null, Bar::class);

	// the bound class has no parent
	Closure::bind(static fn () => [parent::s(), parent::s(...)], null, NoParent::class);

	// undefined method of the bound class
	Closure::bind(static fn () => [self::nope(), self::nope(...)], null, Foo::class);

	// without a bound class self/parent/static stay outside of class scope
	Closure::bind(static fn () => [self::sm(), parent::sm(), static::sm(), self::sm(...)], null);
};

class Container
{

	private static function own(): void
	{
	}

	public function run(): void
	{
		// the bound class wins over the enclosing class
		Closure::bind(static fn () => [self::sm(), parent::sm()], null, Bar::class);
		Closure::bind(static fn () => self::own(), null, Foo::class);
		// the arguments are evaluated in the enclosing class: self::class is Container
		Closure::bind(static fn () => self::own(), null, self::class);
		// a parent-less bound class in a class context
		Closure::bind(static fn () => parent::s(), null, NoParent::class);
	}

}
