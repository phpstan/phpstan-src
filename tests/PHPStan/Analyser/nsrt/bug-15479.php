<?php // lint >= 8.0

declare(strict_types = 1);

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
