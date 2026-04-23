<?php declare(strict_types = 1);

namespace Bug11565;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param iterable<mixed, T> $iterable
 * @return ($iterable is list ? never : list<T>)
 */
function iteratorToList(iterable $iterable): array {
	$list = [];
	foreach ($iterable as $item) {
		$list[] = $item;
	}
	return $list;
}

/**
 * @return iterable<string, string>
 */
function getItems(): iterable {
	yield 'a' => 'foo';
	yield 'b' => 'bar';
}

// Bug: when reassigning to the same variable, conditional return type resolves incorrectly
$items = getItems();
$items = iteratorToList($items);
assertType('list<string>', $items);

// Works fine when using a different variable
$x = getItems();
$items2 = iteratorToList($x);
assertType('list<string>', $items2);

// Same variable reassignment inside if condition (truthy context)
// Non-null context recurses into $expr->var, not $expr->expr, so not affected
$items3 = getItems();
if ($items3 = iteratorToList($items3)) {
	assertType('non-empty-list<string>', $items3);
}
