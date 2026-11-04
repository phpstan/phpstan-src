<?php // lint >= 8.1

declare(strict_types = 1);

namespace ClosureBindScopeAmbiguousClasses;

use Closure;

class Base
{

}

class Foo extends Base
{

	protected const A = 'Foo';

}

class Bar extends Base
{

	protected const A = 'Bar';

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
function doFoo(string $withAncestor, string $noAncestor, string $plain, ?string $nullable): void
{
	// bound to a class that is not exactly one known class: nothing to check
	Closure::bind(static fn () => [self::A, static::A, parent::A, new self(), new static(), new parent()], null, $withAncestor);
	Closure::bind(static fn () => [self::Hearts, self::A, new self()], null, $noAncestor);
	Closure::bind(static fn () => [self::A, parent::A, new self(), new parent()], null, $plain);
	Closure::bind(static fn () => [self::A, new self()], null, $nullable);

	// 'static' and null bind to no class here
	Closure::bind(static fn () => [self::A, new self()], null, 'static');
	Closure::bind(static fn () => [self::A, new self()], null, null);
}

class Container
{

	/**
	 * @param class-string<Foo>|class-string<Bar> $withAncestor
	 * @param class-string $plain
	 */
	public function run(string $withAncestor, string $plain): void
	{
		Closure::bind(static fn () => [self::NOPE, new self(), new parent()], null, $withAncestor);
		Closure::bind(static fn () => [self::NOPE, new self(), new parent()], null, $plain);
	}

}

class WithProtected
{

	protected const PROT = 'p';

	private const PRIV = 'p';

	protected function __construct()
	{
	}

}

/**
 * @param class-string $plain
 * @param class-string<Foo>|class-string<WithProtected> $oneOfThem
 * @param 'static'|null $staticOrNull
 */
function doBar(string $plain, string $oneOfThem, ?string $staticOrNull): void
{
	// an unknown bound class may be the one declaring the members
	Closure::bind(static fn () => [WithProtected::PROT, WithProtected::PRIV, new WithProtected()], null, $plain);

	// one of several classes: accessible from any of them
	Closure::bind(static fn () => [WithProtected::PROT, WithProtected::PRIV, new WithProtected()], null, $oneOfThem);

	// 'static' or null binds to no class here
	Closure::bind(static fn () => [self::A, new self()], null, $staticOrNull);
}
