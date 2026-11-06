<?php

namespace InvalidKeyPhpVersionRange;

function doFoo(float $f): void
{
	$a = [$f => 1];
	$b = [null => 1];

	$c = [];
	$c[$f] = 1;
	$c[null] = 1;
}
