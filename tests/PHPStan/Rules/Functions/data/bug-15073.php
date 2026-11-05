<?php declare(strict_types = 1);

namespace Bug15073Rule;

function wantsString(string $s): void
{
}

/**
 * @param non-empty-array<int|string, int> $intOrString
 * @param non-empty-array<int<0, max>|string, int> $partlyInt
 */
function readKeys(array $intOrString, array $partlyInt): void
{
	// int|string already covers int, so the key type stays strict and is reported
	wantsString(array_key_first($intOrString));

	// the string half of int<0, max>|string widens to the benevolent (int|string)
	wantsString(array_key_first($partlyInt));
}
