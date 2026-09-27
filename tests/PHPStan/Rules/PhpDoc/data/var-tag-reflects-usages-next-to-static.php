<?php

namespace VarTagReflectsUsagesNextToStatic;

function nextToStaticVariable(): void
{
	static $count = 0;
	$count++;
	/** @var list<int> $a */
	$a = [];
	$a[] = 'x';
	var_dump($a, $count);
}
