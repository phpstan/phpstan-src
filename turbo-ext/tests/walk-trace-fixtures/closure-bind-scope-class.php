<?php declare(strict_types = 1);

namespace WalkTraceClosureBindScopeClass;

// self/parent/static inside a closure bound with Closure::bind() follow the
// bound class (MutatingScope::resolveName() / resolveTypeByName()), outside a
// class as well as inside one, for a class-string, an object and the
// default/'static' scope, and for a bound class without a parent
class Foo
{
	protected const A = 'Foo';
	/** @var int */
	protected static $sp = 1;
	protected static function sm(): string { return 'Foo'; }
	public function im(): int { return 1; }
}

class Bar extends Foo
{
}

class NoParent
{
	const X = 1;
}

$foo = new Foo();
\Closure::bind(static fn () => [new self(), new static(), self::sm(), self::$sp, self::A, static::A], null, Foo::class)();
\Closure::bind(static fn () => [new self(), new static(), self::sm(), self::$sp, self::A], null, $foo)();
\Closure::bind(static fn () => [new parent(), parent::sm(), parent::A], null, new Bar())();
\Closure::bind(static fn () => [new parent(), parent::X], null, NoParent::class)();
\Closure::bind(static fn () => [new self(), self::A], null, 'static')();
\Closure::bind(fn () => [self::im(), $this->im()], new Foo(), Foo::class)();

class Container
{
	public function run(): void
	{
		\Closure::bind(static fn () => [new self(), new parent(), parent::sm(), self::A], null, Bar::class)();
		\Closure::bind(static fn () => [new self(), self::class], null, self::class)();
	}
}
