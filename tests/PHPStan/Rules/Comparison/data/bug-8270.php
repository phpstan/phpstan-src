<?php

declare(strict_types = 1);

namespace Bug8270Rule;

function () {
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
	$list[$k]['test'] = true;

	foreach ($list as $item) {
		if ($item['test']) {
			echo $item['value'];
		}
	}
};
