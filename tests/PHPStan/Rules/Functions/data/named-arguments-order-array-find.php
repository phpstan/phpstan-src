<?php declare(strict_types = 1); // lint >= 8.4

namespace NamedArgumentsOrderArrayFind;

/**
 * @param list<string> $arr
 */
function arrayFind(array $arr): void
{
	array_find(callback: static fn (string $v, int $k): bool => true, array: $arr);
	array_find(callback: static fn (int $v, int $k): bool => true, array: $arr);
	array_find_key(callback: static fn (string $v, int $k): bool => true, array: $arr);
	array_find_key(callback: static fn (int $v, int $k): bool => true, array: $arr);
	array_any(callback: static fn (string $v, int $k): bool => true, array: $arr);
	array_any(callback: static fn (int $v, int $k): bool => true, array: $arr);
	array_all(callback: static fn (string $v, int $k): bool => true, array: $arr);
	array_all(callback: static fn (int $v, int $k): bool => true, array: $arr);
}
