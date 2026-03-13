<?php declare(strict_types = 1);

namespace Bug6799b;

use function PHPStan\Testing\assertType;

class HelloWorld
{


	/**
	 * listingAddWhereFilterAtableRoleCategory
	 *
	 * @param string[] $where
	 * @param string $sqlTableName
	 * @param mixed[] $filter
	 * @param string $value
	 *
	 * @return void
	 */
	protected function listingAddWhereFilterAtableDefault(array &$where, string $sqlTableName, array $filter, string $value): void
	{
		if ($value != "" && !empty($filter) && !empty($filter['sql']) && is_string($filter['sql'])) {
			$where[] = "`" . $sqlTableName . "`.`" . (string)$filter['sql'] . "` = '" . $value . "'";
		}
	}

	/**
	 * listingAddWhereFilterAtableFilter
	 *
	 * @param string[] $filterValues
	 * @param string[] $where
	 * @param string[] $tables
	 * @param mixed[] $filters
	 * @return void
	 */
	protected function listingAddWhereFilterAtable(array $filterValues, array &$where, array &$tables, array $filters): void
	{
		if (!empty($filterValues) && !empty($filters)) {
			$whereFilter = array();
			foreach ($filterValues as $type => $value) {
				$this->listingAddWhereFilterAtableDefault($whereFilter, 'xxxxx', $filters[$type], $value);
			}
			assertType('array<string>', $whereFilter);
		}
	}
}
