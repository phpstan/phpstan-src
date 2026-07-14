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

		// The sibling functions already model late static binding, so they do not have the
		// same problem: they keep the subclass possibility instead of pinning to this class.
		assertType('class-string<static(Bug14951\A)>', get_called_class());
		assertType('class-string<$this(Bug14951\A)>', get_class($this));

		// A class-string argument (e.g. self::class) names an exact class, not a runtime
		// value, so it keeps the exact parent — no subclass widening.
		assertType('false', get_parent_class(self::class));

		return get_parent_class($this) === self::class;
	}

}

class B extends A
{

	public function parentOfThis(): void
	{
		assertType('\'Bug14951\\\\A\'|class-string<Bug14951\B>', get_parent_class($this));
		// self::class is the exact class B, so its parent is exactly A.
		assertType("'Bug14951\\\\A'", get_parent_class(self::class));
	}

}

final class FinalNoParent
{

	public function parentOfThis(): void
	{
		// Final class cannot be subclassed, so the parent is exactly false.
		assertType('false', get_parent_class($this));
		// ... and get_called_class() is pinned to the final class.
		assertType("'Bug14951\\\\FinalNoParent'", get_called_class());
	}

}

final class FinalWithParent extends A
{

	public function parentOfThis(): void
	{
		assertType("'Bug14951\\\\A'", get_parent_class($this));
	}

}
