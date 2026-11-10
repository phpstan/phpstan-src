<?php // lint >= 8.3

namespace Bug15224NoSideEffects;

function doFoo(string $s): void
{
	mb_str_pad($s, 5);
	mb_str_pad($s, 5, encoding: 'UTF-8');

	$pad = mb_str_pad(...);
	mb_str_pad(...);
}
