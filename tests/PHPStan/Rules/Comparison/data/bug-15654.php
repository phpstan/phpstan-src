<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15654Match;

/**
 * @template T
 * @param list<T> $list
 * @param callable(T): T $fn
 */
function apply(array $list, callable $fn): void {}

apply(['a', 'b'], static function (string $s): string {
	echo match ($s) { 'a' => 1, 'b' => 2 };
	return $s;
});

apply(['a', 'b'], static function (string $s): string {
	echo match ($s) { 'a' => 1, 'b' => 2, default => throw new \LogicException() };
	return $s;
});
