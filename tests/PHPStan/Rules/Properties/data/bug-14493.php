<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug14493NullsafeProperty;

class Inner {
	public string $value = '';
	public ?string $nullableValue = null;
}

class Middle {
	public ?Inner $inner = null;
	public function getInner(): ?Inner { return $this->inner; }
}

class Outer {
	public function getMiddle(): ?Middle { return null; }
}

class TestPropertyFetch
{
	public function doFoo(Outer $outer): bool
	{
		$value = $outer->getMiddle()?->getInner()?->value;
		if ($value !== 'expected') {
			return false;
		}

		// After narrowing, $outer->getMiddle() should NOT be flagged
		$nullableValue = $outer->getMiddle()?->inner?->nullableValue;
		if ($nullableValue === null) {
			return false;
		}

		return true;
	}
}
