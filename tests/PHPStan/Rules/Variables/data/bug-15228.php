<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15228;

/** @return list<string> */
function strings(): array
{
	return ['x'];
}

/** @param-out int $p */
function writesString(?int &$p): void
{
	$p ??= 'boom';
}

/** @param-out list<int>|null $p */
function validWrite(mixed &$p): void
{
	$p = strings();
	$p ??= [1, 2];
}

/** @param-out string $p */
function keepsOriginalValue(int|string|null &$p): void
{
	$p ??= 'x';
}

/** @param-out string $p */
function keepsOriginalValueTwice(int|string|null &$p): void
{
	$p ??= 'x';
	$p ??= 'y';
}

function coalesceOnClosureCopy(?int $p): \Closure
{
	return function () use ($p): int {
		$p ??= 1;

		return $p;
	};
}
