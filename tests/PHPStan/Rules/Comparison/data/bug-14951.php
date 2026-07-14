<?php declare(strict_types = 1);

namespace Bug14951StrictComparison;

class A
{

	public function isDirectChildOfA(): bool
	{
		// $this can be an instance of a subclass, so get_parent_class($this)
		// can equal self::class for a direct child — this comparison is not always false.
		return get_parent_class($this) === self::class;
	}

}

class B extends A
{
}

class C extends B
{
}
