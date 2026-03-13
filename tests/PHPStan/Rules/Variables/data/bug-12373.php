<?php // lint >= 8.0

namespace Bug12373;

function test(): void
{
	$foo = [];

	[$always_a, $always_b, $always_c] = [rand(0, 1), rand(0, 1), rand(0, 1)];
	if (rand(0, 1)) {
		[$maybe_a, $maybe_b, $maybe_c] = [rand(0, 1), rand(0, 1), rand(0, 1)];
		$flag = true;
	} else {
		$flag = false;
	}

	if ($flag && $always_a !== $maybe_a) {
		$foo[] = 'first';
	}

	if (($always_a && !$always_b) || ($flag && $maybe_a && !$maybe_b)) {
		$foo[] = 'second';
	}

	if (($always_a && !$always_c) || ($flag && $maybe_a && !$maybe_c)) {
		$foo[] = 'third';
	}
}
