<?php // lint >= 7.3

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

	$k = array_key_first($list);
	$list[$k]['test'] = true;

	assertType('non-empty-list<array{test: bool, value: int<0, max>}>', $list);

	foreach ($list as $item) {
		assertType('array{test: bool, value: int<0, max>}', $item);
		if ($item['test']) {
			echo $item['value'];
		}
	}
};
