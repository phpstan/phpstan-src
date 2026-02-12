<?php // lint >= 8.0

namespace Bug13303;

function a(bool $b, bool $c): int {
	return match(true) {
		$b && $c => 1,
		!$b && !$c => 2,
		!$b && $c => 3,
		$b && !$c => 4,
	};
}

function b(bool $b, bool $c): int {
	return match(true) {
		$b && $c,
		!$b && !$c => 1,
		!$b && $c,
		$b && !$c => 2,
	};
}

function c(bool $b, bool $c): int {
	return match(true) {
		$b === true && $c === true => 1,
		$b === false && $c === false => 2,
		$b === false && $c === true => 3,
		$b === true && $c === false => 4,
	};
}

function d(bool $b, bool $c): int {
	// Not exhaustive - should still report error
	return match(true) {
		$b && $c => 1,
		!$b && !$c => 2,
		!$b && $c => 3,
	};
}
