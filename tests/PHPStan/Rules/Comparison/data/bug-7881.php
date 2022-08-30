<?php

namespace Bug7881;

/**
 * @param array<string> $base
 *
 * @return array<int>
 */
function base_str_to_arr(string $str, &$base): array
{
	$arr = [];

	while ('0' === $str || strlen($str) !== 0) {
		echo 'toto';
	}

	return $arr;
}

