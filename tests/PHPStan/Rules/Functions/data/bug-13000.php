<?php declare(strict_types = 1);

namespace Bug13000;

/**
 * @return array{'a':string,'b':string}
 */
function R() : array
{
	$r = [];
	foreach ( ['a' => '1', 'b' => '2'] as $key => $val )
	{
		$r[$key] = $val;
	}
	return $r;
}
