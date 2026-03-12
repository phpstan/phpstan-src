<?php declare(strict_types = 1);

namespace Bug6799;

use function PHPStan\Testing\assertType;

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
			assertType('mixed', $whereFilter);
		}
	}
}

/**
 * @param mixed $foo
 */
function foo($foo): void {}

function testByRefInArray(): void
{
	$a = [];
	assertType('array{}', $a);

	$b = [&$a];
	assertType('mixed', $a);

	foo($b);
	assertType('mixed', $a);
}

function testByRefInArrayWithKey(): void
{
	$a = 'hello';
	assertType("'hello'", $a);

	$b = ['key' => &$a];
	assertType('mixed', $a);
}

function testMultipleByRefInArray(): void
{
	$a = 1;
	$c = 'test';

	$b = [&$a, 'normal', &$c];
	assertType('mixed', $a);
	assertType('mixed', $c);
}
