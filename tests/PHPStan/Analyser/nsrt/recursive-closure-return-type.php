<?php declare(strict_types = 1);

namespace RecursiveClosureReturnType;

use function PHPStan\Testing\assertType;

function test(): void
{
	// the closure calls itself through the by-ref use: its return is the least fixed point
	$fact = function (int $n) use (&$fact) {
		assertType('Closure(int<1, max>): int<1, max>', $fact);
		return $n <= 1 ? 1 : $n * $fact($n - 1);
	};
	assertType('int<1, max>', $fact(5));

	$walk = function (array $a) use (&$walk) {
		$d = 0;
		foreach ($a as $v) {
			if (is_array($v)) {
				$d = max($d, $walk($v) + 1);
			}
		}
		return $d;
	};
	assertType('int<0, max>', $walk([]));

	$countdown = function (int $n) use (&$countdown) {
		return $n === 0 ? 'done' : $countdown($n - 1);
	};
	assertType("'done'", $countdown(3));

	// a declared return type is unchanged
	$fib = static function (int $n) use (&$fib): int {
		return $n < 2 ? $n : $fib($n - 1) + $fib($n - 2);
	};
	assertType('int', $fib(5));
}
