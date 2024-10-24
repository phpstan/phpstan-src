<?php declare(strict_types = 1);

const TYPES = [1, 2, 3];

$types = array_combine(TYPES, array_fill(0, \count(TYPES), false));

$test = rand(1, 4);

if (isset($types[$test])) {
	$types[$test] = true;
}
