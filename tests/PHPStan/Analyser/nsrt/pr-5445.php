<?php

namespace BugPr5445;

use function PHPStan\Testing\assertType;

/**
 * @param array<string, mixed> $aggregation
 * @param non-falsy-string $type
 */
function testTriviallyTrueConditionSkipped(array $aggregation, string $type): void
{
	if (empty($aggregation['field']) && $type !== 'filter') {
		return;
	}

	assertType("array<string, mixed>", $aggregation);
	assertType('non-falsy-string', $type);

	if ($type !== 'filter') {
		assertType("array<string, mixed>", $aggregation);
	}

	assertType('non-falsy-string', $type);
}
