<?php // lint >= 8.0

namespace Bug14150NullsafeMethod;

class HelloWorld
{
	public int $x = 5;

	/**
	 * @return $this
	 */
	public function x(): static
	{
		return $this;
	}

	public function testUnknownMethod(): void
	{
		$this
			->x()
			?->y();
	}
}
