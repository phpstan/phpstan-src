<?php declare(strict_types=1);

namespace Bug10640;

use function PHPStan\Testing\assertType;

$changes = [];
foreach (toAdd() as $add) {
	$changes[$add['id']]['add'][] = doSomething($add);
}
assertType('array<int, array{add: non-empty-array<int<0, max>, 1>}>', $changes);

foreach (toRem() as $del) {
	$changes[$add['id']]['del'][] = doSomething($del);
}
assertType('array<int, array{add: non-empty-array<int<0, max>, 1>, del?: non-empty-array<int<0, max>, 2>}>', $changes);

foreach ($changes as $changeSet) {
	if (isset($changeSet['del'])) {
		doDel($changeSet['del']);
	}
	if (isset($changeSet['add'])) {
		doAdd($changeSet['add']);
	}
}
