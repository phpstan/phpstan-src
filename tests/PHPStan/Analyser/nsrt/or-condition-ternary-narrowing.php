<?php

namespace OrConditionTernaryNarrowing;

use function PHPStan\Testing\assertType;

class A {}
class B {}

/**
 * Mirrors the shape used in TypeCombinator::intersect() where
 * `$constArrayIsI` / `$constArrayIsJ` pair up with `$constArray` /
 * `$otherArray` picked via ternaries inside a nested for-loop over an
 * `$types` list that gets spliced during iteration.
 *
 * @param list<A|B> $types
 */
function loopWithTernary(array $types): void
{
	$typesCount = count($types);
	for ($i = 0; $i < $typesCount; $i++) {
		for ($j = $i + 1; $j < $typesCount; $j++) {
			$iIsA = $types[$i] instanceof A && ($types[$j] instanceof A || $types[$j] instanceof B);
			$jIsA = $types[$j] instanceof A && ($types[$i] instanceof A || $types[$i] instanceof B);

			if ($iIsA || $jIsA) {
				$a = $iIsA ? $types[$i] : $types[$j];

				// `$a` is definitely A: when `$iIsA` holds, the ternary picks
				// `$types[$i]` which is A; when `$iIsA` is false, the outer
				// OR forces `$jIsA` so the ternary picks `$types[$j]` which
				// is A.
				assertType(A::class, $a);
			}
		}
	}
}

/**
 * Same pattern but with plain variables — each `$xIsA`/`$yIsA` records two
 * narrowings (both `$x` and `$y`). The earlier holder (from the first
 * assignment) must survive the second assignment so that inside the
 * outer `if ($xIsA || $yIsA)` the nested `if ($xIsA)` can still fire the
 * conditional holders attached to `$xIsA`.
 *
 * @param A|B $x
 * @param A|B $y
 */
function twoStoredAndNarrowings($x, $y): void
{
	$xBothA = $x instanceof A && $y instanceof A;
	$yBothA = $y instanceof A && $x instanceof A;

	if ($xBothA || $yBothA) {
		if ($xBothA) {
			assertType(A::class, $x);
			assertType(A::class, $y);
		} else {
			assertType(A::class, $x);
			assertType(A::class, $y);
		}
	}
}
