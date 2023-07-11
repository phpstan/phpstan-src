<?php

namespace Bug4612;

function check(int $param): bool { return true; }

/** @var array<int, int> $array */
$array = [];

foreach ($array as $k => $v) {
	if (check($k) && isset($prev)) {
		$array[$prev] = $v;
	}

	$prev = $k;
}
