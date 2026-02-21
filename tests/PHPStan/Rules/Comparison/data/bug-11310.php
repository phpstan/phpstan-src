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
		1 => 'one',
		default => 'default',
	};
}

/** @param int<0, 5>|int<7, 13> $i */
function foo2(int $i): void {
	echo match ($i++) {
		0 => 'zero',
		default => 'default',
	};
}

/** @param int<0, 5>|int<7, 13> $i */
function bar2(int $i): void {
	echo match ($i--) {
		0 => 'zero',
		default => 'default',
	};
}

/** @param int<0, 5>|int<7, 13> $i */
function baz2(int $i): void {
	echo match (++$i) {
		0 => 'zero',
		1 => 'one',
		default => 'default',
	};
}

/** @param 0|1|2|3|4|5 $i */
function foo3(int $i): void {
	echo match ($i++) {
		0 => 'zero',
		default => 'default',
	};
}

/** @param 0|1|2|3|4|5 $i */
function bar3(int $i): void {
	echo match ($i--) {
		0 => 'zero',
		default => 'default',
	};
}

/** @param 0|1|2|3|4|5 $i */
function baz3(int $i): void {
	echo match (++$i) {
		0 => 'zero',
		1 => 'one',
		default => 'default',
	};
}
