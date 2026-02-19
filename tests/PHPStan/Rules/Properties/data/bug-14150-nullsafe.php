<?php // lint >= 8.0

namespace Bug14150NullsafeProperty;

class HelloWorld
{
	public int $x = 5;

	/**
	 * @return $this
	 */
	public function x()
	{
		return $this;
	}

	public function test3(): void
	{
		$this
			?->x;
	}

	public function test4(): void
	{
		$this
			->x()
			?->x;
	}
}
