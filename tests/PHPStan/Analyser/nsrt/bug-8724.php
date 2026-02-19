<?php declare(strict_types = 1);

namespace Bug8724;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-type NumberFilterStructure array{
 *      type: 'equals'|'notEqual'|'lessThan'|'lessThanOrEqual'|'greaterThan'|'greaterThanOrEqual'|'inRange'|'blank'|'notBlank',
 * }
 * @phpstan-type CombinedNumberFilterStructure array{
 *      operator: 'AND'|'OR'
 * }
 */
class HelloWorld
{
	/** @param NumberFilterStructure|CombinedNumberFilterStructure $filter */
	public function test(array $filter): void
	{
		if (isset($filter['operator'])) {
			assertType("array{operator: 'AND'|'OR'}", $filter);
			return;
		}

		assertType("array{type: 'blank'|'equals'|'greaterThan'|'greaterThanOrEqual'|'inRange'|'lessThan'|'lessThanOrEqual'|'notBlank'|'notEqual'}", $filter);
		echo $filter['type'];
	}
}
