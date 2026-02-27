<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug5610;

function foo(int $bar, int $baz): int {
	return match (true) {
		$bar < $baz => 1,
		$bar >= $baz => 2,
	};
}

function foo3(int $bar, int $baz): int {
	return match (true) {
		$bar > $baz => 1,
		$bar <= $baz => 2,
	};
}

function foo4(int $bar, int $baz): int {
	return match (true) {
		$bar <= $baz => 1,
		$bar > $baz => 2,
	};
}

function foo5(int $bar, int $baz): int {
	return match (true) {
		$bar >= $baz => 1,
		$bar < $baz => 2,
	};
}
