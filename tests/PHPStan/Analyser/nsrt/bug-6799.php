<?php declare(strict_types = 1);

namespace Bug6799;

use function PHPStan\Testing\assertType;

/**
 * @param array<int> $items
 */
function functionProcessInts(array &$items): void
{
	$items = [1, 2];
}

/**
 * @param array<string> $items
 */
function functionProcessStrings(array &$items): void
{
	$items = ['a', 'b'];
}

class HelloWorld
{
	/**
	 * @param string[] $where
	 * @param string $sqlTableName
	 * @param mixed[] $filter
	 * @param string $value
	 */
	protected function listingAddWhereFilterAtableDefault(array &$where, string $sqlTableName, array $filter, string $value): void
	{
		if ($value != "" && !empty($filter) && !empty($filter['sql']) && is_string($filter['sql'])) {
			$where[] = "`" . $sqlTableName . "`.`" . (string)$filter['sql'] . "` = '" . $value . "'";
		}
	}

	/**
	 * @param string[] $filterValues
	 * @param string[] $where
	 * @param string[] $tables
	 * @param mixed[] $filters
	 */
	protected function listingAddWhereFilterAtable(array $filterValues, array &$where, array &$tables, array $filters): void
	{
		if (!empty($filterValues) && !empty($filters)) {
			$whereFilter = array();
			foreach ($filterValues as $type => $value) {
				call_user_func_array(array($this, 'listingAddWhereFilterAtableDefault'), array(&$whereFilter, 'xxxx', $filters[$type], $value));
			}
			assertType('array<string>', $whereFilter);
		}
	}

	/**
	 * @param array<mixed> $items
	 * @param-out list<int> $items
	 */
	protected function processWithParamOut(array &$items): void
	{
		$items = [1, 2, 3];
	}

	protected function testParamOut(): void
	{
		$items = [];
		call_user_func_array([$this, 'processWithParamOut'], [&$items]);
		assertType('list<int>', $items);
	}

	/**
	 * @param array<int> $items
	 */
	protected function processInts(array &$items): void
	{
		$items = [1, 2];
	}

	/**
	 * @param array<string> $items
	 */
	protected function processStrings(array &$items): void
	{
		$items = ['a', 'b'];
	}

	/**
	 * @param 'Bug6799\functionProcessInts'|'Bug6799\functionProcessStrings' $function
	 */
	protected function testUnionStringCallbacks(string $function): void
	{
		$items = [];
		call_user_func_array($function, [&$items]);
		assertType('mixed', $items); // Could be array<int>|array<string>
	}

	/**
	 * @param array{$this, 'processInts'}|array{$this, 'processStrings'} $callback
	 */
	protected function testUnionArrayCallbacks(array $callback): void
	{
		$items = [];
		call_user_func_array($callback, [&$items]);
		assertType('mixed', $items); // Could be array<int>|array<string>
	}

	/**
	 * @param 'Bug6799\functionProcessInts'|array{$this, 'processStrings'} $callback
	 */
	protected function testMixedUnionCallback($callback): void
	{
		$items = [];
		call_user_func_array($callback, [&$items]);
		assertType('mixed', $items); // Could be array<int>|array<string>
	}
}
