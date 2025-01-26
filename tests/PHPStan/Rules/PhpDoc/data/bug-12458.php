<?php declare(strict_types = 1);

Namespace Bug12458;

class HelloWorld
{
	/**
	 * @param list<HelloWorld> $a
	 */
	public function test(array $a): void
	{
		/** @var \Closure(): list<HelloWorld> $c */
		$c = function () use ($a): array {
			return $a;
		};
	}
}
