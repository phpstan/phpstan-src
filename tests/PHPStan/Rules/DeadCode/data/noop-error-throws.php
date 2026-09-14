<?php // lint >= 8.0

namespace DeadCodeNoopErrorThrows;

function (int $a, int $b, string $s) {
	$a / $b;
	$a % $b;
	match ($a) {
		1 => 'a',
	};
	new \DateTimeImmutable($s);
};
