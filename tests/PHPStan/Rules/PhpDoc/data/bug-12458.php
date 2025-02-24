<?php declare(strict_types = 1);

namespace Bug12458;

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

	/**
	 * @template T of HelloWorld
	 * @param list<T> $a
	 */
	public function testGeneric(array $a): void
	{
		/** @var \Closure(): list<T> $c */
		$c = function () use ($a): array {
			return $a;
		};
	}
}
