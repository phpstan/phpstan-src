<?php // lint >= 8.0

namespace Bug11802;

class HelloWorld
{
	public function __construct(
		private bool $isFinal,
		private bool $used
	)
	{
	}

	public function doFoo(HelloWorld $x, $y): void
	{
		if ($y !== 'isFinal') {
			$s = $x->{$y};
		}
	}
}
