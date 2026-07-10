<?php // lint >= 8.4

namespace ArrayAllNonEmptyList;

class Foo
{

	/**
	 * @param non-empty-list<mixed> $array
	 */
	public function doFoo(array $array): void
	{
		if (array_all($array, fn ($value, $key) => is_string($key))) {
			echo 'never';
		}
	}

}
