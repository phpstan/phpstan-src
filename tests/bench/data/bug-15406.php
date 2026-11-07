<?php declare(strict_types = 1);

namespace Bug15406Bench;

// closures that call themselves by reference, nested eight levels deep
function run(): int
{
	$f1 = function (int $n1) use (&$f1): int {
		$f2 = function (int $n2) use (&$f2): int {
			$f3 = function (int $n3) use (&$f3): int {
				$f4 = function (int $n4) use (&$f4): int {
					$f5 = function (int $n5) use (&$f5): int {
						$f6 = function (int $n6) use (&$f6): int {
							$f7 = function (int $n7) use (&$f7): int {
								$f8 = function (int $n8) use (&$f8): int {
									return $n8 > 0 ? $f8($n8 - 1) + array_sum(array_map(fn (int $x): int => $f8($x), [1])) : 0;
								};
								return $n7 > 0 ? $f7($n7 - 1) + array_sum(array_map(fn (int $x): int => $f7($x), [1])) : 0;
							};
							return $n6 > 0 ? $f6($n6 - 1) + array_sum(array_map(fn (int $x): int => $f6($x), [1])) : 0;
						};
						return $n5 > 0 ? $f5($n5 - 1) + array_sum(array_map(fn (int $x): int => $f5($x), [1])) : 0;
					};
					return $n4 > 0 ? $f4($n4 - 1) + array_sum(array_map(fn (int $x): int => $f4($x), [1])) : 0;
				};
				return $n3 > 0 ? $f3($n3 - 1) + array_sum(array_map(fn (int $x): int => $f3($x), [1])) : 0;
			};
			return $n2 > 0 ? $f2($n2 - 1) + array_sum(array_map(fn (int $x): int => $f2($x), [1])) : 0;
		};
		return $n1 > 0 ? $f1($n1 - 1) + array_sum(array_map(fn (int $x): int => $f1($x), [1])) : 0;
	};

	return $f1(3);
}
