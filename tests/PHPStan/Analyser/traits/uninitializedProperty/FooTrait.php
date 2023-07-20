<?php // lint >= 8.1

namespace TraitsUnititializedProperty;

trait FooTrait
{
	protected readonly int $x;

	/** @readonly */
	protected int $y;
	protected int $z;

	public function foo(): void
	{
		echo $this->x;
		echo $this->y;
		echo $this->z;
	}
}
