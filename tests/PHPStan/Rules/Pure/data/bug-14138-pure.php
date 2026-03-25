<?php // lint >= 8.0

namespace Bug14138Pure;

/**
 * @phpstan-all-methods-pure
 */
class PureClassWithPromotedProperties
{
	public function __construct(
		protected int $value
	) {}

	public function getValue(): int
	{
		return $this->value;
	}
}

/**
 * @phpstan-all-methods-pure
 */
class PureClassWithSideEffect
{
	public function __construct(
		protected int $value
	) {}

	public function doSomething(): int
	{
		echo 'side effect';
		return $this->value;
	}
}
