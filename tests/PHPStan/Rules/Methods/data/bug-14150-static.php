<?php

namespace Bug14150MethodStatic;

final class HelloWorld
{
	public int $x = 5;

	/**
	 * @return $this
	 */
	public static function x()
	{
		return new self();
	}

	public function testUnknownMethod(): void
	{
		(new HelloWorld())
			::x()
			::y();
	}
}
