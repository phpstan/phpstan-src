<?php // lint >= 8.0

namespace GenericStaticReturnType;

/**
 * @template T
 * @template K
 */
class ClassWithNoConsistentTemplatesTag
{
	/** @return static<string, int> */
	public function notChecked(): static
	{
		return new static();
	}
}

/**
 * @template T of int
 * @phpstan-consistent-templates
 */
class ClassWithConsistentTemplatesTag
{
	/** @return static<string> */
	public function doFoo(): static
	{
		return new static();
	}
}
