<?php

namespace Bug2714;

function (): void
{
	$list = [];

	// creation of unpredictable values at 'type' key
	for ($i = 0; $i < 3; $i++) {
		$list[] = [
			'type' => str_repeat('a', rand(1, 10)),
		];
	}

	$list[] = [
		'type' => 'x',
	];

	foreach ($list as $item) {
		if (in_array($item['type'], ['aaa', 'aaaa'], TRUE)) {
			echo 'OK';
		}
	}
};
