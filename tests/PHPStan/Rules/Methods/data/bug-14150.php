<?php declare(strict_types = 1);

namespace Bug14150Method;

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
			->y();
	}
}
