<?php // lint >= 8.0
declare(strict_types = 1);

namespace OffsetAccessValueAssignmentNullsafe;

class Bar
{
	public int $val;
}

function doFoo(?Bar $bar)
{
	/** @var \ArrayAccess<int,int> $array */
	$array = [
		'a' => 123,
	];

	$array['a'] = $bar?->val;
}
