<?php declare(strict_types = 1);

namespace ExplicitAnalysisContext;

/** @return \ArrayObject<int, int> */
function createInts(): \ArrayObject
{
	$ints = new \ArrayObject([1]);
	return $ints;
}
