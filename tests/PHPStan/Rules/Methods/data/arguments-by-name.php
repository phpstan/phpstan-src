<?php // lint >= 8.0

namespace ArgumentsByNameMethod;

class Foo
{

	/**
	 * @optional-arguments-by-name
	 */
	public function optional(string $dialect, string $group = 'default', bool $allowExtraAttributes = true): void
	{

	}

	/**
	 * @arguments-by-name
	 */
	public function all(string $dialect, string $group = 'default'): void
	{

	}

	/**
	 * @stable-parameter-names
	 */
	public function stable(string $dialect, string $group = 'default'): void
	{

	}

	/**
	 * @no-named-arguments
	 * @optional-arguments-by-name
	 */
	public function conflicting(string $dialect, string $group = 'default'): void
	{

	}

}

/**
 * @optional-arguments-by-name
 */
class ClassLevel
{

	public function doFoo(string $dialect, string $group = 'default'): void
	{

	}

}

class Child extends Foo
{

	public function optional(string $dialect, string $group = 'default', bool $allowExtraAttributes = true): void
	{

	}

	public function all(string $dialect, string $group = 'default'): void
	{

	}

}

interface FooInterface
{

	/**
	 * @arguments-by-name
	 */
	public function doFoo(string $dialect, string $group = 'default'): void;

}

class FooImplementation implements FooInterface
{

	public function doFoo(string $dialect, string $group = 'default'): void
	{

	}

}

function (Foo $foo, ClassLevel $classLevel, Child $child, FooInterface $interface, FooImplementation $implementation): void {
	$foo->optional('openapi-3.1', group: 'read');
	$foo->optional('openapi-3.1', 'read', false);
	$foo->all(dialect: 'openapi-3.1', group: 'read');
	$foo->all('openapi-3.1', group: 'read');
	$foo->stable('openapi-3.1', 'read');
	$foo->conflicting('openapi-3.1', 'read');

	$classLevel->doFoo('openapi-3.1', group: 'read');
	$classLevel->doFoo('openapi-3.1', 'read');

	$child->optional('openapi-3.1', 'read');
	$child->all('openapi-3.1');

	$interface->doFoo('openapi-3.1');
	$implementation->doFoo('openapi-3.1');
	$implementation->doFoo(dialect: 'openapi-3.1');
};
