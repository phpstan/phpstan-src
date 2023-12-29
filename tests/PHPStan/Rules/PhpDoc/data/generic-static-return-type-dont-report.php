<?php // lint >= 8.0

namespace GenericStaticReturnTypeDontReport;

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
