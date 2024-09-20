<?php

namespace Bug10609;

/**
 * @template-covariant A
 */
final class Collection
{
	/**
	 * @param \Closure(A): A $fn
	 */
	public function tap(mixed $fn): void
	{
	}
}
