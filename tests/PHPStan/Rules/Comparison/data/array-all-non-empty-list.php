<?php // lint >= 8.4

namespace ArrayAllNonEmptyList;

class Foo
{

	/**
	 * A predicate that can never hold makes array_all() on a non-empty array
	 * impossible: every element would have to satisfy it, but the array is
	 * guaranteed to have at least one element. A literal-false predicate keeps
	 * the reported closure type stable across PHP versions (a predicate like
	 * is_string($key) is inferred as false on newer PHP but bool on the
	 * downgraded builds).
	 *
	 * @param non-empty-list<mixed> $array
	 */
	public function doFoo(array $array): void
	{
		if (array_all($array, fn ($value, $key) => false)) {
			echo 'never';
		}
	}

}
