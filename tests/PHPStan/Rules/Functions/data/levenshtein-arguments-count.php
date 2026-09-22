<?php

namespace LevenshteinArgumentsCount;

function doFoo(string $a, string $b): void
{
	$c = levenshtein($a, $b);
	$c = levenshtein($a, $b, 1);
	$c = levenshtein($a, $b, 1, 2);
	$c = levenshtein($a, $b, 1, 2, 3);
}
