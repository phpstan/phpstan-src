<?php declare(strict_types = 1);

namespace Bug15431Nsrt;

use stdClass;
use function PHPStan\Testing\assertType;

/**
 * @template T of array<mixed>
 */
class Box
{
}

/**
 * @template TKey of object|array<mixed>
 * @template TValue of object|array<mixed>
 */
class Map
{

	/**
	 * @template T of TKey|TValue
	 * @param (T is object ? T : Box<T>) $value
	 * @return T
	 */
	protected function unbox(object $value)
	{
		assertType('Bug15431Nsrt\Box<T of TKey of array<mixed> (class Bug15431Nsrt\Map, argument)|TValue of array<mixed> (class Bug15431Nsrt\Map, argument) (method Bug15431Nsrt\Map::unbox(), argument)>|(T of TKey of object (class Bug15431Nsrt\Map, argument)|TValue of object (class Bug15431Nsrt\Map, argument) (method Bug15431Nsrt\Map::unbox(), argument))', $value);
	}

	/**
	 * @param (TValue is object ? TValue : Box<TValue>) $v
	 * @param Box<TValue&array<mixed>> $b
	 * @param TValue&object $o
	 */
	public function doFoo($v, $b, $o): void
	{
		assertType('TValue of array<mixed> (class Bug15431Nsrt\Map, argument)|TValue of object (class Bug15431Nsrt\Map, argument)', $this->unbox($v));
		assertType('TValue of array<mixed> (class Bug15431Nsrt\Map, argument)', $this->unbox($b));
		assertType('TValue of object (class Bug15431Nsrt\Map, argument)', $this->unbox($o));
	}

	/**
	 * @param (TKey&array<mixed>)|(TValue&array<mixed>) $arrays
	 * @param (TKey&list<mixed>)|(TValue&list<mixed>) $lists
	 */
	public function doBar($arrays, $lists): void
	{
		assertType('TKey of array<mixed> (class Bug15431Nsrt\Map, argument)|TValue of array<mixed> (class Bug15431Nsrt\Map, argument)', $arrays);
		assertType('TKey of list<mixed> (class Bug15431Nsrt\Map, argument)|TValue of list<mixed> (class Bug15431Nsrt\Map, argument)', $lists);
	}

}

/**
 * @template TA of array{a: int}
 * @template TB of array{a: int}
 * @template TC of array<string, int>
 * @template TD of list<int>
 * @param TA|TB $ab
 * @param TC|TD $cd
 * @param TA|array<int, string> $mixed
 */
function constantArrays($ab, $cd, $mixed): void
{
	assertType('TA of array{a: int} (function Bug15431Nsrt\constantArrays(), argument)|TB of array{a: int} (function Bug15431Nsrt\constantArrays(), argument)', $ab);
	assertType('TC of array<string, int> (function Bug15431Nsrt\constantArrays(), argument)|TD of list<int> (function Bug15431Nsrt\constantArrays(), argument)', $cd);
	assertType('array<int, string>|TA of array{a: int} (function Bug15431Nsrt\constantArrays(), argument)', $mixed);
}

/**
 * @template T of object|array<mixed>
 * @param T|Box<T> $value
 * @return T
 */
function unboxNaked($value)
{
}

/**
 * @template T of object|array<mixed>
 * @param (T is object ? T : Box<T>) $value
 * @return T
 */
function unboxConditional(object $value)
{
}

/**
 * @template T
 * @param T|array<T> $value
 * @return T
 */
function unwrapArray($value)
{
}

/**
 * @param stdClass|Box<array{int}> $a
 * @param int|array<string> $b
 * @param array<string> $c
 */
function doBaz($a, $b, $c): void
{
	assertType('array{int}|stdClass', unboxNaked($a));
	assertType('array{int}|stdClass', unboxConditional($a));
	assertType('int|string', unwrapArray($b));
	assertType('string', unwrapArray($c));
}

/**
 * @template TA of iterable<mixed>
 * @template TB of iterable<int>
 * @param TA $a
 * @param TB $b
 */
function iterables($a, $b): void
{
	$c = rand(0, 1) === 1 ? $a : $b;
	assertType('TA of iterable (function Bug15431Nsrt\iterables(), argument)|TB of iterable<int> (function Bug15431Nsrt\iterables(), argument)', $c);
}
