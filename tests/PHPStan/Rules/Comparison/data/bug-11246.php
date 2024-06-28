<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug11246;

$var = 0;
foreach ([1, 2, 3, 4, 5] as $index) {
	$var++;

	match ($var % 5) {
		1 => 'c27ba0',
		2 => '5b9bd5',
		3 => 'ed7d31',
		4 => 'ffc000',
		default => '674ea7',
	};
}
