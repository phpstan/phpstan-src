<?php

declare(strict_types = 1);

namespace Bug8270;

use function PHPStan\Testing\assertType;

function doFoo() {
	/** @var non-empty-list<array{test: false, value: int}> $list */
	$list = [];
	$list[0]['test'] = true;

	foreach ($list as $item) {
		assertType('array{test: bool, value: int}', $item);
		if ($item['test']) {
			assertType('true', $item['test']);
			echo $item['value'];
		}
	}
}

function doBar() {
	$list = [];

	for ($i = 0; $i < 10; $i++) {
		$list[] = [
			'test' => false,
			'value' => rand(),
		];
	}

	if ($list === []) {
		return;
	}

	$k = array_key_first($list);
	assertType('int<0, max>', $k);
	$list[$k]['test'] = true;

	foreach ($list as $item) {
		assertType('array{test: bool, value: int<0, max>}', $item);
		if ($item['test']) {
			echo $item['value'];
		}
	}
}
