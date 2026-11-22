<?php // lint >= 8.4

declare(strict_types = 1);

namespace Bug15322;

/**
 * @phpstan-all-methods-pure
 * @method string magicFoo(string $foo)
 */
class Foo
{
	public function __call(string $foo, array $args): mixed
	{
		return $foo;
	}

	public function foo(string $foo): string
	{
		return $foo;
	}
}

/**
 * @phpstan-all-methods-impure
 * @method string magicImpure(string $foo)
 */
class Impure
{
	public function __call(string $foo, array $args): mixed
	{
		return $foo;
	}
}

class Bar
{
	/**
	 * @phpstan-pure
	 */
	public function bar(): string
	{
		return new Foo()->foo('foo');
	}

	/**
	 * @phpstan-pure
	 */
	public function magicBar(): string
	{
		return new Foo()->magicFoo('foo');
	}

	/**
	 * @phpstan-pure
	 */
	public function magicImpure(): string
	{
		return new Impure()->magicImpure('foo');
	}
}
