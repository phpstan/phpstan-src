<?php

namespace Bug14758;

/**
 * @param string $s
 * @param array<string, string> $arr
 */
function doBar(string $s, $arr) {
	if (ctype_digit($s)) {
		var_dump($arr[$s]);
	}
}
