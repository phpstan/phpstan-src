<?php // lint >= 8.0

namespace Bug14150NullsafeMethod;

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

	public function testUnknownMethod(): void
	{
		$this
			->x()
			?->y();
	}
}
