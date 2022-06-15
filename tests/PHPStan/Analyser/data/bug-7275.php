<?php declare(strict_types = 1);

namespace Bug7275;

class HelloWorld
{
	/**
	 * @param array<HelloWorld|null> $collectionWithPotentialNulls
	 *
	 * @return mixed[]
	 */
	public function doSomething(array $collectionWithPotentialNulls): array
	{
		return !in_array(null, $collectionWithPotentialNulls, true)
			? $this->doSomethingElse($collectionWithPotentialNulls)
			: [];
	}

	/**
	 * @param array<HelloWorld> $collectionWithoutNulls
	 *
	 * @return mixed[]
	 */
	public function doSomethingElse(array $collectionWithoutNulls): array
	{
		return [];
	}
}
