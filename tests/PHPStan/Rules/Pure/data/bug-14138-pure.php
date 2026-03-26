<?php // lint >= 8.0

namespace Bug14138Pure;

/**
 * @phpstan-all-methods-pure
 */
class PureClassWithPromotedProps
{
	public function __construct(
		protected int $value
	) {}

	public function getValue(): int
	{
		return $this->value;
	}
}

class TestCaller
{
	/** @phpstan-pure */
	public function callPureConstructor(): PureClassWithPromotedProps
	{
		return new PureClassWithPromotedProps(1);
	}
}
