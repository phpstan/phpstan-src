<?php declare(strict_types = 1);

namespace Bug8467c;

/**
 * @param int[] $a
 * @return void
 */
function foo(array $a, int $key): void
{
	$k = [];
	foreach ($a as $v) {
		$k[$key][] = $v;
	}

	echo $v;
	foreach ($k as $values) {
		echo $v;
		foreach ($values as $v) {
			echo $v;
		}
	}
}
