<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug5191;

function foo(int $value, ?string $suffix): int {
	match ($suffix) {
		'k', 'K' => $pow = 1,
		'm', 'M' => $pow = 2,
		'g', 'G' => $pow = 3,
		default => $pow = 0,
	};
	return $value * (1024 ** $pow);
}

function bar(int $value, ?string $suffix): int {
	match ($suffix) {
		'k', 'K' => $pow = 1,
		'm', 'M' => $pow = 2,
		'g', 'G' => $pow = 3,
	};
	return $value * (1024 ** $pow); // no default, $pow might not be defined
}

function baz(int $x): int {
	match (true) {
		$x > 0 => $result = 1,
		$x < 0 => $result = -1,
		default => $result = 0,
	};
	return $result;
}
