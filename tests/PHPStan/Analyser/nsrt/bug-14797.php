<?php declare(strict_types = 1);

namespace Bug14797;

use function PHPStan\Testing\assertType;

/**
 * @param array<non-empty-string, non-empty-string>|list<non-empty-string> $values
 */
function toValues(array $values): void
{
	assertType('array<non-empty-string, non-empty-string>|list<non-empty-string>', $values);
	if (!array_is_list($values)) {
		assertType('array<non-empty-string, non-empty-string>', $values);
	} else {
		assertType('list<non-empty-string>', $values);
	}
}

/**
 * @param array<string, int>|list<int> $values
 */
function withString(array $values): void
{
	assertType('array<string, int>|list<int>', $values);
	if (array_is_list($values)) {
		assertType('list<int>', $values);
	} else {
		assertType('array<string, int>', $values);
	}
}

/**
 * @param array<int|string, int>|list<int> $values
 */
function stillMergeWhenIntKeysPossible(array $values): void
{
	// when the non-list part already allows integer keys, the integer keys
	// are not exclusive to the list part, so we keep the merged form
	assertType('array<int|string, int>', $values);
	if (!array_is_list($values)) {
		assertType('non-empty-array<int|string, int>', $values);
	}
}
