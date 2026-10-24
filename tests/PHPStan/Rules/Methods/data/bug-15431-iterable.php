<?php declare(strict_types = 1);

namespace Bug15431Iterable;

/**
 * @template TKey of object|iterable<mixed>
 * @template TValue of object|array<mixed>
 */
class Map
{

	/**
	 * @param TKey&iterable<mixed> $a
	 * @param TValue&array<mixed> $b
	 * @param (TValue&array<mixed>)|(TKey&iterable<mixed>) $c
	 * @return TKey|TValue
	 */
	public function doFoo($a, $b, $c)
	{
		if (rand(0, 1) === 1) {
			return $a;
		}
		if (rand(0, 1) === 1) {
			return $b;
		}

		return $c;
	}

}

class Foo
{

	/**
	 * @template TA of iterable<mixed>
	 * @template TB of iterable<mixed>
	 * @param TA $a
	 * @param TB $b
	 * @return TA|TB
	 */
	public function doFoo($a, $b)
	{
		return rand(0, 1) === 1 ? $a : $b;
	}

}
