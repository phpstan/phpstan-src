<?php declare(strict_types = 1);

namespace Bug13628;

function test(mixed $param): string {

	$a = is_array($param) ? array_filter($param) : $param;
	if ($a && is_array($a)) {
		return 'array';
	}
	else {
		return 'not-array';
	}

}
