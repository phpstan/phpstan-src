<?php declare(strict_types = 1);

namespace Bug14543;

use function PHPStan\Testing\assertType;

/**
 * @return non-empty-list<int>
 */
function getItems(): array
{
	return [1, 2, 3];
}

$result = [];

foreach (['a', 'b'] as $key) {
	foreach (getItems() as $i) {
		$result[] = $i;
	}
}


assertType('non-empty-list<int>', $result);
