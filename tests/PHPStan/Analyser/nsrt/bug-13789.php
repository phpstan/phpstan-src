<?php

namespace Bug13789;

use function PHPStan\Testing\assertType;

/** @return list<non-empty-array<mixed>> */
function get_list_of_non_empty_array(): array
{
	return [['a' => 1]];
}

/** @param array<mixed> $row */
function sanitize(array &$row): void
{
}

function (): void {
	$foo = get_list_of_non_empty_array();
	assertType('list<non-empty-array<mixed>>', $foo);

	foreach ($foo as &$row) {
		sanitize($row); // $row might be empty after that line
		assertType('array<mixed>', $row);
		$row[random_bytes(2)] = random_bytes(2); // $row is definitely non-empty after that line
		assertType('non-empty-array<mixed>', $row);
	}
	unset($row);

	assertType('list<non-empty-array<mixed>>', $foo);
};
