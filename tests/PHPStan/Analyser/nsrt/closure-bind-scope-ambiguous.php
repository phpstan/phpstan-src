<?php // lint >= 8.1

declare(strict_types = 1);

namespace ClosureBindScopeAmbiguous;

use Closure;
use function PHPStan\Testing\assertType;

class Base
{

	public static function make(): static
	{
		return new static(); // @phpstan-ignore new.static
	}

	public static function base(): self
	{
		return new self();
	}

}

class Foo extends Base
{

	public const A = 'Foo';

}

class Bar extends Base
{

	public const A = 'Bar';

}

enum Suit: string
{

	case Hearts = 'H';

}

/**
 * @param class-string<Foo>|class-string<Bar> $withAncestor
 * @param class-string<Foo>|class-string<Suit> $noAncestor
 * @param class-string $plain
 */
function doFoo(string $withAncestor, string $noAncestor, string $plain, string $str): void
{
	// bound to one of several classes: self/static is the closest class they all extend
	assertType('static(ClosureBindScopeAmbiguous\Base)', Closure::bind(static fn () => static::make(), null, $withAncestor)());
	assertType('ClosureBindScopeAmbiguous\Base', Closure::bind(static fn () => self::make(), null, $withAncestor)());
	assertType('ClosureBindScopeAmbiguous\Base', Closure::bind(static fn () => self::base(), null, $withAncestor)());

	// ...and without one, or when the class is unknown, nothing is known about it
	assertType('*ERROR*', Closure::bind(static fn () => self::A, null, $withAncestor)());
	assertType('*ERROR*', Closure::bind(static fn () => self::Hearts, null, $noAncestor)());
	assertType('*ERROR*', Closure::bind(static fn () => self::A, null, $plain)());
	assertType('*ERROR*', Closure::bind(static fn () => self::A, null, $str)()); // @phpstan-ignore argument.type

	// new and ::class name the closest class all candidates extend, or some class
	assertType('array{ClosureBindScopeAmbiguous\\Base, static(ClosureBindScopeAmbiguous\\Base), ClosureBindScopeAmbiguous\\Base}', Closure::bind(static fn () => [new self(), new static(), new parent()], null, $withAncestor)());
	assertType('array{class-string<ClosureBindScopeAmbiguous\\Base>, class-string<static(ClosureBindScopeAmbiguous\\Base)>, class-string<ClosureBindScopeAmbiguous\\Base>}', Closure::bind(static fn () => [self::class, static::class, parent::class], null, $withAncestor)());
	assertType('array{object, object}', Closure::bind(static fn () => [new self(), new static()], null, $noAncestor)());
	assertType('array{class-string, class-string, class-string}', Closure::bind(static fn () => [self::class, static::class, parent::class], null, $noAncestor)());
	assertType('array{object, class-string}', Closure::bind(static fn () => [new self(), self::class], null, $plain)());

	// so do the type hints of the bound closure
	assertType('static-Closure(ClosureBindScopeAmbiguous\\Base, ClosureBindScopeAmbiguous\\Base): ClosureBindScopeAmbiguous\\Base', Closure::bind(static fn (self $x, parent $y): self => $x, null, $withAncestor));
	assertType('static-Closure(mixed): mixed', Closure::bind(static fn (self $x): parent => $x, null, $plain));
	assertType('static-Closure(mixed): mixed', Closure::bind(static fn (self $x): self => $x, null, $noAncestor));
	assertType('static-Closure(ClosureBindScopeAmbiguous\\Foo, ClosureBindScopeAmbiguous\\Base): ClosureBindScopeAmbiguous\\Base', Closure::bind(static fn (self $x, parent $y): parent => $y, null, Foo::class));
}

/**
 * @param-closure-this object $closure
 */
function withObjectThis(Closure $closure): void
{
}

// a closure bound to an object of no known class: self names some class
withObjectThis(function (): void {
	assertType('class-string', self::class);
	assertType('object', new self());
});

function closureCall(object $object): void
{
	assertType('class-string', (fn () => self::class)->call($object));
}

class Container extends Base
{

	public const OWN = 'own';

	/**
	 * @param class-string<Foo>|class-string<Bar> $withAncestor
	 * @param class-string<Foo>|class-string<Suit> $noAncestor
	 * @param class-string $plain
	 */
	public function doFoo(string $withAncestor, string $noAncestor, string $plain): void
	{
		// inside a class an ambiguous bound class does not fall back to the enclosing one
		assertType('array{ClosureBindScopeAmbiguous\\Base, class-string<ClosureBindScopeAmbiguous\\Base>, ClosureBindScopeAmbiguous\\Base}', Closure::bind(static fn () => [new self(), self::class, self::make()], null, $withAncestor)());
		assertType('array{object, class-string}', Closure::bind(static fn () => [new self(), self::class], null, $noAncestor)());
		assertType('array{object, class-string, *ERROR*}', Closure::bind(static fn () => [new self(), self::class, self::OWN], null, $plain)());
	}

}
