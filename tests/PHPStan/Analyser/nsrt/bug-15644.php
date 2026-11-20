<?php

declare(strict_types=1);

namespace Bug15644;

use function PHPStan\Testing\assertType;

/**
 * @template T of bool
 * @param (T is true ? array{recordinfo: array{id: string}, ...<string, mixed>} : array{recordinfo?: never, ...<string, mixed>})&array{a: string, b: int|''} $row
 * @param T $modifiable
 */
function conditional(array $row, bool $modifiable = false): void
{
	assertType("array{a: string, b: ''|int}", $row);
	$a = $row['a'] ?: null;
	assertType("array{a: string, b: ''|int}", $row);
	assertType("''|int", $row['b']);
}

/**
 * @param array{a: string, b: int|''} $row
 */
function plain(array $row): void
{
	assertType("array{a: string, b: ''|int}", $row);
	$a = $row['a'] ?: null;
	assertType("array{a: string, b: ''|int}", $row);
	assertType("''|int", $row['b']);
}
