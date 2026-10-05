<?php declare(strict_types = 1);

namespace ClosureBindScopeProperties;

use Closure;

class Foo
{

	/** @var int */
	protected static $sp = 1;

	/** @var int */
	private static $pp = 2;

}

final class Bar extends Foo
{

}

class NoParent
{

	/** @var int */
	public static $y = 1;

}

function (Foo $foo, Bar $bar): void {
	// the bound class's protected properties, through self/parent/static
	Closure::bind(static fn () => [self::$sp, static::$sp], null, Foo::class);
	Closure::bind(static fn () => parent::$sp, null, Bar::class);
	Closure::bind(static function (): void {
		self::$sp = 2;
		static::$sp = 3;
	}, null, Foo::class);
	Closure::bind(static function (): void {
		parent::$sp = 2;
	}, null, Bar::class);

	// an object newScope binds its class
	Closure::bind(static fn () => self::$sp, null, $foo);
	Closure::bind(static fn () => parent::$sp, null, $bar);

	// private properties of Foo are not accessible from the Bar scope
	Closure::bind(static fn () => self::$pp, null, Bar::class);
	Closure::bind(static function (): void {
		self::$pp = 3;
	}, null, Bar::class);

	// the bound class has no parent
	Closure::bind(static fn () => parent::$y, null, NoParent::class);
	Closure::bind(static function (): void {
		parent::$y = 2;
	}, null, NoParent::class);

	// undefined property of the bound class
	Closure::bind(static fn () => self::$nope, null, Foo::class);

	// without a bound class self/parent/static stay outside of class scope
	Closure::bind(static fn () => [self::$sp, parent::$sp, static::$sp], null);
	Closure::bind(static function (): void {
		self::$sp = 1;
	}, null);
};
