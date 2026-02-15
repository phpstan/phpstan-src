<?php declare(strict_types = 1);

namespace Bug13809;

use function PHPStan\Testing\assertType;

/**
 * @param list<mixed> $list
 */
function foo(array $list): void
{
	foreach ($list as &$value) {
		$value = 'foo';
	}

	assertType('list<mixed>', $list);
}

/**
 * @param list<mixed> $list
 */
function bar(array $list): void
{
	foreach ($list as $key => &$value) {
		$value = 'foo';
	}

	assertType("list<'foo'>", $list);
}

/**
 * @param list<string> $list
 */
function baz(array $list): void
{
	foreach ($list as &$value) {
		$value = 'bar';
	}

	assertType('list<string>', $list);
}

/**
 * @param list<int> $list
 */
function qux(array $list): void
{
	foreach ($list as &$value) {
		$value = $value + 1;
	}

	assertType('list<int>', $list);
}
