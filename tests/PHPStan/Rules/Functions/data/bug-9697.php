<?php

namespace Bug9697;


function doFoo(): void
{
	$oldItems = [1,2,3];
	$newItems = [1,2];

	$comparator = fn (int $a, int $b):int => $a - $b;

	usort($oldItems, $comparator);

	array_udiff(
		$oldItems,
		$newItems,
		$comparator,
	);
}
