<?php declare(strict_types = 1);

namespace Bug12137;

/** @phpstan-consistent-constructor */
abstract class ParentClass
{
	protected function __construct()
	{
	}

	public static function create(): static
	{
		return new static();
	}
}

class ChildClass extends ParentClass
{
	private function __construct()
	{
	}
}
