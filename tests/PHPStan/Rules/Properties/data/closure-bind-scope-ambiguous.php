<?php declare(strict_types = 1);

namespace ClosureBindScopeAmbiguousProperties;

use Closure;

class Base
{

}

class Foo extends Base
{

	/** @var int */
	protected static $sp = 1;

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
	Closure::bind(static fn () => [self::$sp, static::$sp, parent::$sp], null, $withAncestor);
	Closure::bind(static function (): void {
		self::$sp = 1;
		parent::$sp = 2;
	}, null, $plain);

	// 'static' binds to no class here
	Closure::bind(static fn () => self::$sp, null, 'static');
}

class WithProtected
{

	/** @var int */
	protected static $prot = 1;

	/** @var int */
	private static $priv = 2;

	protected int $instanceProt = 3;

}

/**
 * @param class-string $plain
 */
function doBar(string $plain, WithProtected $object): void
{
	// an unknown bound class may be the one declaring the members
	Closure::bind(static fn () => [WithProtected::$prot, WithProtected::$priv, $object->instanceProt], null, $plain);
	Closure::bind(static function () use ($object): void {
		WithProtected::$prot = 2;
		WithProtected::$priv = 3;
		$object->instanceProt = 4;
	}, null, $plain);
}
