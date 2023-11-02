<?php

namespace Bug9753;

use function PHPStan\Testing\assertType;

function (): void {
	$items = [];
	$array = [1,2,3,4,5];

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

	assertType('non-empty-list<1|2|3|4|5>|null', $items);
};
