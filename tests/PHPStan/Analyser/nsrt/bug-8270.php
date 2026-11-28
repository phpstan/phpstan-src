<?php

namespace Bug8270;

use function PHPStan\Testing\assertType;

$list = [];

for ($i = 0; $i < 10; $i++) {
	$list[] = [
		'test' => false,
		'value' => rand(),
	];
}

// TODO: sort list by value asc...
$k = array_key_first($list);
$list[$k]['test'] = true; // <--- assign only first item!

foreach ($list as $item) {
	assertType('array{test: bool, value: int<0, max>}', $item);
	if ($item['test']) {
		echo $item['value'];
	}
}
