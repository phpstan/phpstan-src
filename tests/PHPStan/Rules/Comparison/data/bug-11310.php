<?php // lint >= 8.0

namespace Bug11310;

/** @param int<0, max> $i */
function foo(int $i): void {
	echo match ($i++) {
		0 => 'zero',
		default => 'default',
	};
}

/** @param int<0, max> $i */
function bar(int $i): void {
	echo match ($i--) {
		0 => 'zero',
		default => 'default',
	};
}

/** @param int<0, max> $i */
function baz(int $i): void {
	echo match (++$i) {
		0 => 'zero',
		default => 'default',
	};
}
