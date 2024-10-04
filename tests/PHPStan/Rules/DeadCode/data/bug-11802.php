<?php declare(strict_types=1);

namespace Bug11802;

class HelloWorld
{
	public function __construct(
		private bool $isFinal
	)
	{
	}

	public function doFoo(HelloWorld $x, string $y): void
	{
		$s = $x->{$y()};
	}
}
