<?php // lint >= 8.1

declare(strict_types = 1);

namespace ClosureBindScopeAmbiguousMethods;

use Closure;

class Base
{

}

class Foo extends Base
{

	protected static function sm(): void
	{
	}

}

class Bar extends Base
{

}

/**
 * @param class-string<Foo>|class-string<Bar> $withAncestor
 * @param class-string $plain
 */
function doFoo(string $withAncestor, string $plain): void
{
	// bound to a class that is not exactly one known class: nothing to check
	Closure::bind(static fn () => [self::sm(), static::sm(), parent::sm(), self::sm(...)], null, $withAncestor);
	Closure::bind(static fn () => [self::sm(), parent::sm(), self::sm(...)], null, $plain);

	// 'static' binds to no class here
	Closure::bind(static fn () => [self::sm(), self::sm(...)], null, 'static');
}

class WithProtected
{

	protected static function prot(): int
	{
		return 1;
	}

	private function priv(): int
	{
		return 2;
	}

}

/**
 * @param class-string $plain
 */
function doBar(string $plain, WithProtected $object): void
{
	// an unknown bound class may be the one declaring the members
	Closure::bind(static fn () => [WithProtected::prot(), $object->priv(), WithProtected::prot(...)], null, $plain);
}
