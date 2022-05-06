<?php // lint >= 8.0

namespace Bug6115;

$a = 5;
try {
	$b = match ($a) {
		1 => [0],
		2 => [1],
		3 => [2],
	};
} catch (\UnhandledMatchError $e) {
	// not dead
}

try {
	$b = match ($a) {
		default => [0],
	};
} catch (\UnhandledMatchError $e) {
	// dead
}

try {
	$b = match ($a) {
		5 => [0],
	};
} catch (\UnhandledMatchError $e) {
	// dead
}
