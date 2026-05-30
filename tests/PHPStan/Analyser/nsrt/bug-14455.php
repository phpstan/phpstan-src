<?php declare(strict_types = 1);

namespace Bug14455;

use function PHPStan\Testing\assertType;

/**
 * @param array<string, mixed> $aggregation
 * @param non-falsy-string $type
 */
function testTriviallyTrueConditionSkipped(array $aggregation, string $type): void
{
	if (empty($aggregation['field']) && $type === 'filter') {
		return;
	}

	assertType("array<string, mixed>", $aggregation);
	assertType('non-falsy-string', $type);

	if ($type === 'filter') {
		assertType("non-empty-array<string, mixed>&hasOffset('field')", $aggregation);
	} else {
		assertType("array<string, mixed>", $aggregation);
	}

	assertType('non-falsy-string', $type);
}
