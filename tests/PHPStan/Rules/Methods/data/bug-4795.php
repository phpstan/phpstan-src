<?php // lint >= 8.0

namespace Bug4795;

abstract class BaseDTO
{

	final protected static function create(): static
	{
		$class = static::class;

		return new $class();
	}

	abstract public static function parse(): static;

}

final class ConcreteDTO extends BaseDTO
{

	public string $foo;

	public static function parse(): static
	{
		$instance = self::create();

		$instance->foo = 'bar';

		return $instance;
	}
}

class NonFinalConcreteDTO extends BaseDTO
{

	public string $foo;

	public static function parse(): static
	{
		$instance = self::create();

		$instance->foo = 'bar';

		return $instance;
	}
}
