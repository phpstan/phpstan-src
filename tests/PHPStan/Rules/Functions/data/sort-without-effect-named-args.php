<?php // lint >= 8.0

declare(strict_types = 1);

namespace SortWithoutEffectNamedArgs;

/** @param list<string> $list */
function ksortNamedArgument(array $list): void
{
	ksort(array: $list);
}

/** @param list<string> $list */
function ksortNamedArgumentWithStringFlags(array $list): void
{
	ksort(flags: SORT_STRING, array: $list);
}
