<?php // lint >= 7.4

namespace Bug4227;

class Foo
{
	private bool $property;
	private int $count = 0;
	private ?string $string = null;

	public function __construct(bool $property)
	{
		$this->property = $property;
	}

	public function count(): int
	{
		return $this->count;
	}
}
