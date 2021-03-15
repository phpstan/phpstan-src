<?php

namespace Bug3120;

class A {
	/** @return static */
	public static function getInstance() {
		$class = static::class;
		return new $class();
	}
}

final class AChild extends A {
	public static function getInstance() {
		return new AChild();
	}
}

class Test
{
	final public function __construct()
	{}

	/**
	 * @return static
	 */
	public function foo(): self
	{
		return self::bar(new static());
	}

	/**
	 * @phpstan-template T of Test
	 * @phpstan-param T $object
	 * @phpstan-return T
	 */
	public function bar(Test $object): self
	{
		return $object;
	}

}
