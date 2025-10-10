<?php declare(strict_types = 1);

namespace Bug13628;

/**
 * @param mixed $param
 * @return string
 */
function test($param) {

	$a = is_array($param) ? array_filter($param) : $param;
	if ($a && is_array($a)) {
		return 'array';
	}
	else {
		return 'not-array';
	}

}
