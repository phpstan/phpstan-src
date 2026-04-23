<?php declare(strict_types = 1);

namespace Bug13789;

use function PHPStan\Testing\assertType;

/** @return list<non-empty-array<mixed>> */
function get_list_of_non_empty_array(): array { return [[1]]; }

/** @param array<mixed> $row */
function sanitize(array &$row): void { }

function doFoo(): void
{
	$foo = get_list_of_non_empty_array();
	assertType('list<non-empty-array<mixed>>', $foo);

	foreach ($foo as &$row) {
		sanitize($row);
		assertType('array<mixed>', $row);
		$row[random_bytes(2)] = random_bytes(2);
		assertType('non-empty-array<mixed>', $row);
	}
	unset($row);

	assertType('list<non-empty-array<mixed>>', $foo);
}
