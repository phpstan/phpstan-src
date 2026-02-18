<?php

declare(strict_types = 1);

namespace Bug13984;

$list = [
	'a',
	'b',
	'c',
];

/** @param list<string> $list */
function acceptList(array $list): bool {
	if (count($list) < 1) {
		return false;
	}

	$compare = ['a', 'b', 'c'];

	foreach($list as $key => $item) {
		foreach ($compare as $k => $v) {
			if ($item === $v && $v !== 'a') {
				unset($list[$key]);
			}
		}
	}

	if (count($list) > 0) {
		return true;
	}

	return false;
}

assert(acceptList($list) === true);
