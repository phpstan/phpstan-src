<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15450;

use function PHPStan\Testing\assertType;

/** @param array<string, mixed> $v */
function isList(array $v): void
{
	if (array_is_list($v)) {
		assertType('array{}', $v);
	} else {
		assertType('non-empty-array<string, mixed>', $v);
	}
}

/** @param array<string, mixed> $v */
function isEmpty(array $v): void
{
	if ($v === []) {
		assertType('array{}', $v);
	}
}

/** @param non-empty-array<string, mixed> $v */
function isNonEmptyList(array $v): void
{
	if (array_is_list($v)) {
		assertType('*NEVER*', $v);
	} else {
		assertType('non-empty-array<string, mixed>', $v);
	}
}
