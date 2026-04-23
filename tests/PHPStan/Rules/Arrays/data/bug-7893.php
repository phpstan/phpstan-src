<?php declare(strict_types = 1);

namespace Bug7893;

$result = [
	'_labels' => [],
];

foreach ([1, 2, 3] as $id) {
	$result[] = $id;
	$result['_labels'][] = 'asda';
}
