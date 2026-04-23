<?php

namespace Bug9753;

use function PHPStan\Testing\assertType;

function doFoo(): void {
	$items = [];
	$array = [1,2,3,4,5];

	foreach ($array as $entry) {
		assertType('array{}|list{0: 1, 1?: 2, 2?: 3}|null', $items);
		if (isset($items)) {
			if (count($items) > 2) {
				$items = null;
			} else {
				$items[] = $entry;
			}
		}
		assertType('list{0: 1|2|3|4|5, 1?: 1|2|3|4|5, 2?: 1|2|3|4|5, 3?: 1|2|3|4|5}|null', $items);
	}

	assertType('null', $items);
};

/**
 * @param list<1|2|3|4|5> $array
 */
function doFoo2(array $array): void {
	$items = [];

	foreach ($array as $entry) {
		assertType('list<1|2|3|4|5>|null', $items);
		if (isset($items)) {
			if (count($items) > 2) {
				$items = null;
			} else {
				$items[] = $entry;
			}
		}
		assertType('non-empty-list<1|2|3|4|5>|null', $items);
	}

	assertType('list<1|2|3|4|5>|null', $items);
};
