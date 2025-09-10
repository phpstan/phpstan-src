<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11289;

abstract class SomeAbstractClass
{
	private bool $someValue = true;

	/**
	 * @phpstan-assert-if-true =static $other
	 */
	public function equals(?self $other): bool
	{
		return $other instanceof static
			&& $this->someValue === $other->someValue;
	}
}

class SomeConcreteClass extends SomeAbstractClass
{
	public function __construct(
		private bool $someOtherValue,
	) {}

	public function equals(?SomeAbstractClass $other): bool
	{
		return parent::equals($other)
			&& $this->someOtherValue === $other->someOtherValue;
	}
}

$a = new SomeConcreteClass(true);
$b = new SomeConcreteClass(false);

var_dump($a->equals($b), $b->equals($b));
