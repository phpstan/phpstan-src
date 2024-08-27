<?php

namespace ArrayMapCallable81;

use function PHPStan\Testing\assertType;
use function strval as str;

class Foo
{
	/**
	 * @template T of int
	 * @param T $n
	 * @return (T is 3 ? 'Fizz' : (T is 5 ? 'Buzz' : T))
	 */
	public static function fizzbuzz(int $n): int|string
	{
		return match ($n) {
			3 => 'Fizz',
			5 => 'Buzz',
			default => $n,
		};
	}

	public function doFoo(): void
	{
		$a = range(0, 1);

		assertType("array{'0', '1'}", array_map('strval', $a));
		assertType("array{'0', '1'}", array_map(strval(...), $a));
		assertType("array{'0', '1'}", array_map(str(...), $a));
		assertType("array{'0'|'1', '0'|'1'}", array_map(fn ($v) => strval($v), $a));
		assertType("array{'0'|'1', '0'|'1'}", array_map(fn ($v) => (string)$v, $a));
	}

	public function doFizzBuzz(): void
	{
		assertType("array{1, 2, 'Fizz', 4, 'Buzz', 6}", array_map([__CLASS__, 'fizzbuzz'], range(1, 6)));
		assertType("array{1, 2, 'Fizz', 4, 'Buzz', 6}", array_map([$this, 'fizzbuzz'], range(1, 6)));
		assertType("array{1|'Buzz'|'Fizz', 2|'Buzz'|'Fizz', 3|'Buzz'|'Fizz', 4|'Buzz'|'Fizz', 5|'Buzz'|'Fizz', 6|'Buzz'|'Fizz'}", array_map(self::fizzbuzz(...), range(1, 6)));
		assertType("array{1|'Buzz'|'Fizz', 2|'Buzz'|'Fizz', 3|'Buzz'|'Fizz', 4|'Buzz'|'Fizz', 5|'Buzz'|'Fizz', 6|'Buzz'|'Fizz'}", array_map($this->fizzbuzz(...), range(1, 6)));
	}

}
