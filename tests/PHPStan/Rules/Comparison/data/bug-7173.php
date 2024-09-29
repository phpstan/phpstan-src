<?php

namespace Bug7173;

function (): void {
	$a1 = [
		'item2' => 0,
		'item1' => 0,
	];

	call_user_func(function () use (&$a1) {
		$a1['item2'] = 3;
		$a1['item1'] = 1;
	});

	if (['item2' => 3, 'item1' => 1] === $a1) {
		throw new \Exception();
	}
};
