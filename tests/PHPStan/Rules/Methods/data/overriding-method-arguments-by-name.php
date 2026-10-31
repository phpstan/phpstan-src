<?php // lint >= 8.0

namespace OverridingMethodArgumentsByName;

class Foo
{

	/**
	 * @arguments-by-name
	 */
	public function all(string $dialect, string $group = 'default'): void
	{
	}

	/**
	 * @optional-arguments-by-name
	 */
	public function optional(string $dialect, string $group = 'default', string ...$rest): void
	{
	}

	/**
	 * @stable-parameter-names
	 */
	public function stable(string $dialect, string $group = 'default'): void
	{
	}

	public function untagged(string $dialect): void
	{
	}

	/**
	 * @no-named-arguments
	 * @arguments-by-name
	 */
	public function conflicting(string $dialect): void
	{
	}

}

class Bar extends Foo
{

	public function all(string $name, string $group = 'default'): void
	{
	}

	public function optional(string $name, string $category = 'default', string ...$others): void
	{
	}

	public function stable(string $dialect, string $category = 'default'): void
	{
	}

	public function untagged(string $name): void
	{
	}

	public function conflicting(string $name): void
	{
	}

}

/**
 * @optional-arguments-by-name
 */
interface FooInterface
{

	public function doFoo(string $dialect, string $group = 'default'): void;

}

class FooImplementation implements FooInterface
{

	public function doFoo(string $name, string $category = 'default'): void
	{
	}

}

abstract class AbstractConstructor
{

	/**
	 * @arguments-by-name
	 */
	abstract public function __construct(string $dialect);

}

class Constructor extends AbstractConstructor
{

	public function __construct(string $name)
	{
	}

}

/**
 * @arguments-by-name
 */
class ClassLevel
{

	public function doFoo(string $dialect): void
	{
	}

	/**
	 * @no-named-arguments
	 */
	public function noNamed(string $dialect): void
	{
	}

}

class ChildOfClassLevel extends ClassLevel
{

	public function doFoo(string $name): void
	{
	}

	public function noNamed(string $name): void
	{
	}

}
