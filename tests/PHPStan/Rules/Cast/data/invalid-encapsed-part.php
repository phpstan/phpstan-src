<?php

namespace InvalidPartOfEncapsedString;

class ClassWithToString
{
	public function __toString(): string
	{
		return 'str';
	}
}

function foo(
	string $str,
	\stdClass $std,
	bool $bool,
	int $int,
	float $float,
	array $array,
	ClassWithToString $objectWithToString
) {
	$null = null;
	$resource = fopen('php://input');
	assert($resource !== false);
	"$str bar";
	"$std bar";
	"$bool bar";
	"$int bar";
	"$float bar";
	"$array bar";
	"$objectWithToString bar";
	"$null bar";
	"$resource bar";
}

/**
 * @param string|\stdClass $std
 * @param string|bool $bool
 * @param string|int $int
 * @param string|float $float
 * @param string|array $array
 * @param string|ClassWithToString $objectWithToString
 * @param string|null $null
 * @param string|resource $resource
 */
function checkUnions(
	$std,
	$bool,
	$int,
	$float,
	$array,
	$objectWithToString,
	$null,
	$resource
) {
	"$std bar";
	"$bool bar";
	"$int bar";
	"$float bar";
	"$array bar";
	"$objectWithToString bar";
	"$null bar";
	"$resource bar";
}
