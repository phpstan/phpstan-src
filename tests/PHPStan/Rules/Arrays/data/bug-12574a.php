<?php declare(strict_types=1);

namespace Bug12574a;

function doFoo() {
	$i = 10;
	$x = [];
	while ($i-- > 0) {
		$r = rand(1, 10);
		if (!isset($x[$r])) {
			$x[$r] = 1;
		}
		var_dump($x[$r]);
	}
}
