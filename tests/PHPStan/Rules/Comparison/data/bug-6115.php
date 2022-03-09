<?php // lint >= 8.0

namespace Bug6115;

$array = [1, 2, 3];
try {
	foreach ($array as $value) {
		$b = match ($value) {
			1 => 0,
			2 => 1,
		};
	}
} catch (\UnhandledMatchError $e) {
}
