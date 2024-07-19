<?php

namespace Bug11361;

function foo(): void
{
	$bar = function(&$var) {
		$var++;
	};
	$a = array(0);
	$bar($a[0]);
}
