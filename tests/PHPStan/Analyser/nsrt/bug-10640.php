<?php declare(strict_types=1);

namespace Bug10640;

use function PHPStan\Testing\assertType;

$changes = [];
foreach (toAdd() as $add) {
	$changes[$add['id']]['add'][] = doSomething($add);
}
assertType('array<array{add: non-empty-list}>', $changes);

foreach (toRem() as $del) {
	$changes[$add['id']]['del'][] = doSomething($del);
}
assertType('array<non-empty-array{add?: non-empty-list, del?: non-empty-list}>', $changes);

foreach ($changes as $changeSet) {
	if (isset($changeSet['del'])) {
		doDel($changeSet['del']);
	}
	if (isset($changeSet['add'])) {
		doAdd($changeSet['add']);
	}
}

function doSomething($s) {}
function toAdd($s) {}
function toRem($s) {}
function doDel($s) {}
function doAdd($s) {}
