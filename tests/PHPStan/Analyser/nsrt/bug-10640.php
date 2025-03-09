<?php

namespace Bug10640;

use function PHPStan\Testing\assertType;

function (array $a, array $b): void {
	$changes = [];
	foreach ($a as $add) {
		$changes[$add['id']]['add'][] = 1;
	}
	foreach ($b as $del) {
		$changes[$del['id']]['del'][] = 2;
	}
	assertType('array<non-empty-array{add?: non-empty-list<1>, del?: non-empty-list<2>}>', $changes);
};
