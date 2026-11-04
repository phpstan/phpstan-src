<?php declare(strict_types = 1);

namespace WalkTraceClosureBindScopeAmbiguous;

// a Closure::bind() newScope that is not exactly one known class: one of
// several classes (with and without a common ancestor) or a class-string
// whose class is unknown keeps the closure bound without naming a class;
// 'static' and null bind to none
class Base
{
	public static function make(): static { return new static(); }
	public static function base(): self { return new self(); }
}

class Foo extends Base
{
	public const A = 'Foo';
}

class Bar extends Base
{
	public const A = 'Bar';
}

class Lone
{
	public const A = 'Lone';
}

/**
 * @param class-string<Foo>|class-string<Bar> $withAncestor
 * @param class-string<Foo>|class-string<Lone> $noAncestor
 * @param class-string $plain
 */
function f(string $withAncestor, string $noAncestor, string $plain, string $str, ?string $nullable): void
{
	\Closure::bind(static fn () => [static::make(), self::make(), self::base(), self::A, new self(), new static()], null, $withAncestor)();
	\Closure::bind(static fn () => [self::A, new self(), static::A], null, $noAncestor)();
	\Closure::bind(static fn () => [self::A, new self(), parent::A], null, $plain)();
	\Closure::bind(static fn () => [self::A, new self()], null, $str)();
	\Closure::bind(static fn () => [self::A, new self()], null, $nullable)();
	\Closure::bind(static fn () => [self::A, new self()], null, 'static')();
	\Closure::bind(static fn () => [self::A, new self()], null, null)();
}

class Container
{
	public const A = 'Container';

	/**
	 * @param class-string<Foo>|class-string<Bar> $withAncestor
	 * @param class-string $plain
	 */
	public function run(string $withAncestor, string $plain): void
	{
		\Closure::bind(static fn () => [self::A, new self(), static::A], null, $withAncestor)();
		\Closure::bind(static fn () => [self::A, new self(), static::A], null, $plain)();
	}
}
