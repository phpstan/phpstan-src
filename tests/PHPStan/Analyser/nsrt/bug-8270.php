<?php

namespace Bug8270;

use function PHPStan\Testing\assertType;

function (): void {
	$list = [];

	for ($i = 0; $i < 10; $i++) {
		$list[] = [
			'test' => false,
			'value' => rand(),
		];
	}
	assertType('non-empty-list<array{test: false, value: int<0, max>}>', $list);

	// TODO: sort list by value asc...
	$k = array_key_first($list);
	$list[$k]['test'] = true; // <--- assign only first item!

	foreach ($list as $item) {
		if ($item['test']) {
			echo $item['value'];
		}
	}
	assertType('non-empty-list<array{test: bool, value: int<0, max>}>&hasOffsetValue(0, array{test: true, value: int<0, max>})', $list);

};
