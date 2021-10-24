<?php // lint >= 8.0
declare(strict_types = 1);

namespace OffsetAccessAssignmentNullsafe;

class Bar
{
	public int $val;
}

function doFoo(?Bar $bar)
{
	$str = 'abcd';
	$str[$bar?->val] = 'ok';
}
