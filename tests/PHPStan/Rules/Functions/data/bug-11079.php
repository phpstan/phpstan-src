<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug11079;

use \Ds\Pair;

/**
 * @param iterable<K, V> $iterable
 * @param callable(K, V): Pair<KReturn, VReturn> $mapper
 *
 * @template K
 * @template V
 * @template KReturn
 * @template VReturn
 */
function genFcn(iterable $iterable, callable $mapper): void
{
}

class HelloWorld
{
	/**
	 * @param int[] $a
	 */
	public function sayHello(array $a): void
	{
		genFcn(
			$a,
			static fn (mixed $_, int $v): Pair => new Pair($v, $v),
		);
	}
}
