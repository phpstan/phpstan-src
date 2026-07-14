<?php declare(strict_types = 1);

namespace Bug14951;

use function PHPStan\Testing\assertType;

class A
{

	public function isDirectChildOfA(): bool
	{
		// $this can be an instance of a subclass, so get_parent_class($this)
		// may return A::class (for a direct child) — not just false.
		assertType('class-string<Bug14951\A>|false', get_parent_class($this));

		return get_parent_class($this) === self::class;
	}

}

class B extends A
{

	public function parentOfThis(): void
	{
		assertType('\'Bug14951\\\\A\'|class-string<Bug14951\B>', get_parent_class($this));
	}

}

final class FinalNoParent
{

	public function parentOfThis(): void
	{
		// Final class cannot be subclassed, so the parent is exactly false.
		assertType('false', get_parent_class($this));
	}

}

final class FinalWithParent extends A
{

	public function parentOfThis(): void
	{
		assertType("'Bug14951\\\\A'", get_parent_class($this));
	}

}
