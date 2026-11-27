<?php declare(strict_types = 1);

namespace Bug14797;

use function PHPStan\Testing\assertType;

/**
 * @param array<non-empty-string, non-empty-string> | list<non-empty-string> $values
 * @return list<non-empty-string>
 */
function to_values(
	array $values,
): array {
	assertType('array<non-empty-string, non-empty-string>|list<non-empty-string>', $values);
	if (!array_is_list($values)) {
		assertType('non-empty-array<non-empty-string, non-empty-string>', $values);
	}

	return array_values($values);
}
