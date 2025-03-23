<?php

namespace Bug12716;

function (): void {
	$items = [];
	$a = function () use (&$items) {
		$x = 'a';
		$items[] = $x;
		if (count($items) >= 10) {
			var_dump(count($items));
			$items = [];
		}
	};
	$i = 0;
	while ($i++ <= 100) {
		$a();
	}
};
