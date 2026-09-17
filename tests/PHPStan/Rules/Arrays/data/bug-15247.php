<?php declare(strict_types = 1);

namespace Bug15247DuplicateKeys;

/** @param list<int> $list */
function unknownSpread(array $list): array
{
	return [...$list, 0 => 'x'];
}

/** @param list<int> $list */
function unknownSpreadImplicitKeys(array $list): array
{
	return [...$list, 'a', 'b'];
}

function constantSpreadDuplicate(): array
{
	return [...[1, 2], 0 => 'x'];
}

function constantSpreadNoDuplicate(): array
{
	return [...[1, 2], 2 => 'x'];
}

function constantSpreadImplicitKey(): array
{
	return [...[1, 2], 'x'];
}

function constantSpreadAfterItem(): array
{
	return [1, ...[2, 3], 1 => 'x'];
}

/** @param list<int> $list */
function unknownSpreadThenExplicitIntKeys(array $list): array
{
	return [...$list, 5 => 'a', 'b', 6 => 'c'];
}

function unknownKeyThenExplicitIntKeys(int $k): array
{
	return [$k => 'z', 5 => 'a', 'b', 6 => 'c'];
}

/** @param list<int> $list */
function unknownSpreadWithDuplicateStringKeys(array $list): array
{
	return [...$list, 'a' => 1, 'a' => 2];
}

function spreadAfterPhpIntMaxKey(): array
{
	return [9223372036854775807 => 1, ...[2]];
}

function spreadCrossingPhpIntMax(): array
{
	return [9223372036854775806 => 1, ...[2, 3]];
}

function spreadReachingPhpIntMax(): array
{
	return [9223372036854775806 => 1, ...[2], 9223372036854775807 => 3];
}
