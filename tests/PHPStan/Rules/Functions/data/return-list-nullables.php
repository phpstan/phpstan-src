<?php

namespace ReturnListNullables;

/**
 * @param array<string|null> $x
 * @return array<string>|null
 */
function doFoo(array $x): ?array
{
	$list = [];
	foreach ($x as $v) {
		$list[] = $v;
	}

	return $list;
}
