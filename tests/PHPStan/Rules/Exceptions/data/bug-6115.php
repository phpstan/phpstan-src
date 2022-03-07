<?php // lint >= 8.0

namespace Bug6115;

$a = 5;
try {
	$exportTypes[] = match ($a) {
		1 => [0],
		2 => [1],
		3 => [2],
	};
} catch (\UnhandledMatchError $e) {
}
