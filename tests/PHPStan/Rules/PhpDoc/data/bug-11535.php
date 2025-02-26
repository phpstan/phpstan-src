<?php declare(strict_types = 1);

namespace Bug11535;

/** @var \Closure(string): array<int> */
$a = function(string $b) {
	return [1,2,3];
};

/** @var \Closure(array): array */
$a = function(array $b) {
	return $b;
};

/** @var \Closure(string): string */
$a = function(string $b) {
	return $b;
};
