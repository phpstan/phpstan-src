<?php declare(strict_types = 1);

namespace Bug15248DuplicateKeys;

/**
 * @param array<string, int> $rest
 * @param array<int, int> $intKeyed
 */
function foo(string $key, array $rest, array $intKeyed): void
{
	$a = [9223372036854775807 => 1, $key => 2, ...$rest];
	$b = [9223372036854775807 => 1, 2];
	$c = [9223372036854775807 => 1, 2, 3];
	$d = [9223372036854775806 => 1, 2, 3];

	$e = [...$intKeyed, 0 => 'x'];
	$f = [...$intKeyed, 'y'];
	$g = [0 => 'x', ...$intKeyed, 1 => 'y'];

	$h = ['a' => 1, ...$rest, 'a' => 2];
}
