<?php

namespace Bug10595;

function doFoo() {
	$test = [];
	$test[0] = [0, []];
	$test[0][1]['h'] = 'h';

	foreach ($test as $value) {
		sort($value[1]);
		$value[1] = implode(',', $value[1]);
		$label = 'test' . $value[1];
	}
}

