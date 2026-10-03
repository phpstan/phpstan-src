<?php declare(strict_types = 1);

namespace ClosureBindScope;

use Closure;
use function PHPStan\Testing\assertType;

class Foo
{

	protected const A = 'Foo';

	/** @var int */
	protected static $staticProp = 1;

	/** @return 'Foo' */
	protected static function staticMethod(): string
	{
		return 'Foo';
	}

}

final class Bar extends Foo
{

	protected const A = 'Bar';

	/** @return 'Bar' */
	protected static function staticMethod(): string // @phpstan-ignore method.childReturnType
	{
		return 'Bar';
	}

}

// Class constants: explicit names resolve regardless of scope, self/parent follow the bound scope.
assertType("'Foo'", Closure::bind(static fn () => Foo::A, null, Foo::class)());
assertType("'Bar'", Closure::bind(static fn () => Bar::A, null, Bar::class)());
assertType("'Foo'", Closure::bind(static fn () => self::A, null, Foo::class)());
assertType("'Bar'", Closure::bind(static fn () => self::A, null, Bar::class)());
assertType("'Foo'", Closure::bind(static fn () => parent::A, null, Bar::class)());

// ::class magic constant.
assertType("'ClosureBindScope\\\\Foo'", Closure::bind(static fn () => self::class, null, Foo::class)());
assertType("'ClosureBindScope\\\\Foo'", Closure::bind(static fn () => parent::class, null, Bar::class)());

// Static method calls.
assertType("'Foo'", Closure::bind(static fn () => self::staticMethod(), null, Foo::class)());
assertType("'Bar'", Closure::bind(static fn () => self::staticMethod(), null, Bar::class)());
assertType("'Foo'", Closure::bind(static fn () => parent::staticMethod(), null, Bar::class)());

// Static property access.
assertType('int', Closure::bind(static fn () => self::$staticProp, null, Foo::class)());

// Instantiation via self/parent/static.
assertType('ClosureBindScope\Foo', Closure::bind(static fn () => new self(), null, Foo::class)());
assertType('ClosureBindScope\Foo', Closure::bind(static fn () => new parent(), null, Bar::class)());

class Container
{

	public function doBindFromInsideClass(): void
	{
		// Even when Closure::bind() is called from inside another class, the bound
		// scope wins over the enclosing class.
		assertType("'Foo'", Closure::bind(static fn () => self::A, null, Foo::class)());
		assertType("'Bar'", Closure::bind(static fn () => self::A, null, Bar::class)());
		assertType('ClosureBindScope\Foo', Closure::bind(static fn () => new self(), null, Foo::class)());
	}

}
