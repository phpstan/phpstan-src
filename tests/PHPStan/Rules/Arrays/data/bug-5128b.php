<?php

namespace Bug5128b;

/**
 * @phpstan-type NumberFilter array{
 *      filterType: 'number',
 *      type:  'equals'|'notEqual'|'lessThan'|'lessThanOrEqual'|'greaterThan'|'greaterThanOrEqual'|'inRange'|'blank'|'notBlank',
 *      filter?: integer,
 *      filterTo?: integer
 * }
 *
 * @phpstan-type MultipleNumberFilters array{
 *      filterType: 'number',
 *      operator: 'AND'|'OR',
 *      condition1: NumberFilter,
 *      condition2: NumberFilter,
 * }
 */
class Converter
{
	/**
	 * @param  NumberFilter|MultipleNumberFilters  $filter
	 * @return array<mixed>
	 */
	private function convertNumberFilters(array $filter): array
	{
		if (isset($filter['condition1'], $filter['condition2'])) {
			$conditions = [
				$this->convertNumberFilter($filter['condition1']),
				$this->convertNumberFilter($filter['condition2']),
			];
		} else {
			$conditions = [
				$this->convertNumberFilter($filter),
			];
		}

		return $conditions;
	}

	/**
	 * @param  NumberFilter  $filter
	 * @return array<mixed>
	 */
	public function convertNumberFilter(array $filter): array
	{
		return [];
	}
}
