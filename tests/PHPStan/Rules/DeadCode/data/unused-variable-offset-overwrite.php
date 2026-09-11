<?php declare(strict_types = 1);

namespace UnusedVariableOffsetOverwrite;

function overwritten(int $i): int
{
	$a = [];
	$a[$i] = 1;
	$a['x'] = 2;
	return $a['x'];
}

function preserved(int $i): array
{
	$a = [];
	$a[$i] = 1;
	$a['x'] = 2;
	return $a;
}
