<?php // lint >= 8.0

namespace Bug6692;

/**
 * @template T
 */
class Wrapper
{
	/**
	 * @return $this<string>
	 */
	public function change(): static
	{
		return $this;
	}
}

/**
 * @template T
 * @extends Wrapper<T>
 *
 * @method self<string> change()
 */
class SubWrapper extends Wrapper
{
}
