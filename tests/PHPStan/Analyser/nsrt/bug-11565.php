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

$items = getItems();
$items = iteratorToList($items);
assertType('list<string>', $items);

$x = getItems();
$items2 = iteratorToList($x);
assertType('list<string>', $items2);

$items3 = getItems();
if ($items3 = iteratorToList($items3)) {
	assertType('non-empty-list<string>', $items3);
}

// Property fetch as LHS - exercises removeExpr for non-Variable expressions
class Holder {
	/** @var iterable<string, string> */
	public iterable $items;
}

function testPropertyFetch(Holder $holder): void {
	$holder->items = iteratorToList($holder->items);
	assertType('list<string>', $holder->items);
}

// Array dim fetch as LHS
/**
 * @param array{items: iterable<string, string>} $data
 */
function testArrayDimFetch(array $data): void {
	$data['items'] = iteratorToList($data['items']);
	assertType('list<string>', $data['items']);
}
