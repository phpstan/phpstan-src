<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug12363;

/**
 * @template Y of 'a'|'b'
 * @param Y $y
 */
function f(int $x, string $y = 'a'): void {}

// Spreading associative array with required + optional template param
f(...['x' => 5, 'y' => 'b']);

// Without spread - should also work
f(5, 'b');

/**
 * @template Y of 'a'|'b'
 * @param Y $y
 */
function g(string $y = 'a'): void {}

// Without preceding required arg - already works
g(...['y' => 'b']);
