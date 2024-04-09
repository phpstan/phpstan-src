<?php declare(strict_types = 1);

namespace Bug10814;

class HelloWorld
{
	/** @param \Closure(\DateTime): void $fx */
	public function foo($fx): void
	{
		$fx(new \DateTimeImmutable());
	}
}
