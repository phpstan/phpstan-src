<?php declare(strict_types = 1);

namespace Bug6799;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param string[] $where
	 * @param mixed[] $filter
	 */
	protected function addFilter(array &$where, array $filter, string $value): void
	{
		if ($value != "" && !empty($filter) && !empty($filter['sql']) && is_string($filter['sql'])) {
			$where[] = (string)$filter['sql'] . " = '" . $value . "'";
		}
	}

	/**
	 * @param string[] $filterValues
	 * @param mixed[] $filters
	 */
	protected function test(array $filterValues, array $filters): void
	{
		if (!empty($filterValues) && !empty($filters)) {
			$whereFilter = array();
			foreach ($filterValues as $type => $value) {
				call_user_func_array(array($this, 'addFilter'), array(&$whereFilter, $filters[$type], $value));
			}
			assertType('array<string>', $whereFilter);
		}
	}
}

function testSimple(): void
{
	$arr = [];
	some_function(1, [&$arr, 'foo']);
	assertType('mixed', $arr);
}

function testDirectFunction(): void
{
	$result = [];
	call_user_func_array('Bug6799\modify_by_ref', [&$result, 'value']);
	assertType('array<string>', $result);
}

/** @param callable $callback */
function testUnresolvableCallback($callback): void
{
	$arr = [];
	call_user_func_array($callback, [&$arr, 'foo']);
	assertType('mixed', $arr);
}

function testCallbackNotByRef(): void
{
	$arr = [];
	call_user_func_array('Bug6799\some_function', [1, [&$arr, 'foo']]);
	assertType('mixed', $arr);
}

/**
 * @param string[] $arr
 */
function modify_by_ref(array &$arr, string $value): void
{
	$arr[] = $value;
}

/**
 * @param mixed $x
 */
function some_function(int $a, $x): void
{
}
