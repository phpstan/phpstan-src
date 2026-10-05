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

// static:: follows the bound class like self:: does.
assertType("'Bar'", Closure::bind(static fn () => static::A, null, Bar::class)());
assertType('static(ClosureBindScope\\Foo)', Closure::bind(static fn () => new static(), null, Foo::class)());

// A closure nested in a bound closure is bound too...
assertType("'Bar'", Closure::bind(static fn () => (static fn () => self::A)(), null, Bar::class)());
assertType("'BarFoo'", Closure::bind(static fn () => Closure::bind(static fn () => self::A, null, Bar::class)() . self::A, null, Foo::class)());

// ...but a class declared in one has its own self/static.
Closure::bind(static fn () => new class {

	public const Z = 'z';

	public function f(): void
	{
		assertType("'z'", self::Z);
		assertType("'z'", static::Z);
	}

}, null, Foo::class);
Closure::bind(static fn () => new class extends Foo {

	public function f(): void
	{
		assertType("'Foo'", self::A);
		assertType("'Foo'", parent::A);
	}

}, null, Bar::class);

// The bound class is the one the call site evaluated, so an object newScope works too.
function objectScope(Foo $foo, Bar $bar): void
{
	assertType('ClosureBindScope\Foo', Closure::bind(static fn () => new self(), null, $foo)());
	assertType('static(ClosureBindScope\Foo)', Closure::bind(static fn () => new static(), null, $foo)());
	assertType('ClosureBindScope\Foo', Closure::bind(static fn () => new parent(), null, $bar)());
	assertType("'Foo'", Closure::bind(static fn () => self::staticMethod(), null, $foo)());
	assertType("'Bar'", Closure::bind(static fn () => self::staticMethod(), null, $bar)());
	assertType("'Foo'", Closure::bind(static fn () => self::A, null, $foo)());
	assertType("'Foo'", Closure::bind(static fn () => parent::A, null, $bar)());
}

// The newScope argument is evaluated where the call is, not inside the closure body.
/** @param class-string<Bar> $cls */
function variableScope(string $cls): void
{
	assertType("'Bar'", Closure::bind(static function () {
		return self::A;
	}, null, $cls)());
	assertType("'Bar'", Closure::bind(static function () use ($cls) {
		$cls = Foo::class;
		return self::A;
	}, null, $cls)());
}

class Container
{

	public function doBindFromInsideClass(): void
	{
		// Even when Closure::bind() is called from inside another class, the bound
		// scope wins over the enclosing class.
		assertType("'Foo'", Closure::bind(static fn () => self::A, null, Foo::class)());
		assertType("'Bar'", Closure::bind(static fn () => self::A, null, Bar::class)());
		assertType('ClosureBindScope\Foo', Closure::bind(static fn () => new self(), null, Foo::class)());
		// the default 'static' scope keeps the enclosing class
		assertType("'ClosureBindScope\\\\Container'", Closure::bind(static fn () => self::class, null, 'static')());
		assertType("'ClosureBindScope\\\\Container'", Closure::bind(static fn () => self::class, null)());
		// the arguments are evaluated in the enclosing class
		assertType("'ClosureBindScope\\\\Container'", Closure::bind(static fn () => self::class, null, self::class)());
		// parent is the bound class's parent, not the enclosing class's
		assertType("'Foo'", Closure::bind(static fn () => parent::staticMethod(), null, Bar::class)());
		assertType('ClosureBindScope\Foo', Closure::bind(static fn () => new parent(), null, Bar::class)());
	}

}
