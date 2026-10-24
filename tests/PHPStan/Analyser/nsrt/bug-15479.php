<?php // lint >= 8.0

namespace Bug15479;

use function PHPStan\Testing\assertType;

/** @param array<string, mixed> $a */
function f(array $a): void {
	$ok = array_key_exists('k', $a) && ($a['k'] === null || is_int($a['k']));
	if ($ok) { assertType('int|null', $a['k']); }
}

/** @param array<string, mixed> $a */
function g(array $a): void {
	$ok = ($a['k'] === null || is_int($a['k']));
	if ($ok) { assertType('int|null', $a['k']); }
}

/** @param array<string, mixed> $a */
function h(array $a): void {
	if (array_key_exists('k', $a) && ($a['k'] === null || is_int($a['k']))) { assertType('int|null', $a['k']); }
}

function i(mixed $v): void {
	$ok = $v === null || is_int($v);
	if ($ok) { assertType('int|null', $v); }
}

/** @param array<string, mixed> $a */
function negated(array $a): void {
	$ok = ($a['k'] !== null && !is_int($a['k']));
	if (!$ok) {
		assertType('int|null', $a['k']);
	} else {
		assertType('mixed~(int|null)', $a['k']);
	}
}

/** @param array<string, mixed> $a */
function ternary(array $a): void {
	$ok = $a['k'] === null || is_int($a['k']) ? 1 : 0;
	if ($ok === 1) { assertType('int|null', $a['k']); }
}

/** @param array<string, array<string, mixed>> $a */
function nested(array $a): void {
	$ok = ($a['k']['j'] === null || is_int($a['k']['j']));
	if ($ok) { assertType('int|null', $a['k']['j']); }
}

/** @param array<string, mixed> $a */
function typeChecks(array $a): void {
	$ok = (is_string($a['k']) || is_int($a['k']));
	if ($ok) { assertType('int|string', $a['k']); }
}

/** @param array<string, mixed> $a */
function constantOrType(array $a): void {
	$ok = ($a['k'] === 1 || is_string($a['k']));
	if ($ok) { assertType('1|string', $a['k']); }
}

/** @param array<string, mixed> $a */
function elseBranch(array $a): void {
	$ok = ($a['k'] === null || is_string($a['k']));
	if ($ok) {
		assertType('string|null', $a['k']);
	} else {
		assertType('mixed~(string|null)', $a['k']);
	}
}
