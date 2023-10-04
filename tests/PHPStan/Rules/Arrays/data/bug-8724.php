<?php

namespace Bug8724;

/**
 *
 * @phpstan-type NumberFilterStructure array{
 *      type: 'equals'|'notEqual'|'lessThan'|'lessThanOrEqual'|'greaterThan'|'greaterThanOrEqual'|'inRange'|'blank'|'notBlank',
 * }
 * @phpstan-type CombinedNumberFilterStructure array{
 *      operator: 'AND'|'OR'
 * }
 */
class HelloWorld
{
	/** @param  NumberFilterStructure|CombinedNumberFilterStructure  $filter */
	public function test(array $filter): void
	{
		if (isset($filter['operator'])) {
			return;
		}

		echo $filter['type'];
	}
}
