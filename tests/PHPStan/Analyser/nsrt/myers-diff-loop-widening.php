<?php declare(strict_types = 1);

namespace MyersDiffLoopWidening;

use function PHPStan\Testing\assertType;
use function count;

/**
 * Regression test: a comparison like `$v[$k - 1] < $v[$k + 1]` inside the body
 * of a nested loop combined with `$k === -$d` style narrowing on the inner
 * counter used to strip `BenevolentUnionType` off the iterable value type of
 * the array, downgrading `(int|float)` to a strict `int|float` during loop
 * fixed-point convergence. That, in turn, caused real return-type false
 * positives where the function actually returns `int` — see the Myers diff
 * implementation in phpstan/phpdoc-parser's `Differ::calculateTrace()`.
 */
class Foo
{

	/**
	 * @param array<int, mixed> $old
	 * @param array<int, mixed> $new
	 * @return array{array<int, array<int, int>>, int, int}
	 */
	public function calculateTrace(array $old, array $new): array
	{
		$n = count($old);
		$m = count($new);
		$max = $n + $m;
		$v = [1 => 0];
		$trace = [];
		for ($d = 0; $d <= $max; $d++) {
			$trace[] = $v;
			for ($k = -$d; $k <= $d; $k += 2) {
				if ($k === -$d || ($k !== $d && $v[$k - 1] < $v[$k + 1])) {
					$x = $v[$k + 1];
				} else {
					$x = $v[$k - 1] + 1;
				}

				$y = $x - $k;
				while ($x < $n && $y < $m) {
					$x++;
					$y++;
				}

				assertType('int<0, max>', $x);
				assertType('int<0, max>', $y);

				$v[$k] = $x;
				if ($x >= $n && $y >= $m) {
					return [$trace, $x, $y];
				}
			}
		}

		throw new \Exception('Should not happen');
	}

}
