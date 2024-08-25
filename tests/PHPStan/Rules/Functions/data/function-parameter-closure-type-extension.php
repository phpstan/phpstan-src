<?php declare(strict_types = 1); // lint >= 8.1

namespace FunctionParameterClosureTypeExtension;

/** @param array{0: array{string, int<-1, max>}, 1?: array{''|'foo', int<-1, max>}, 2?: array{''|'bar', int<-1, max>}, 3?: array{'baz', int<-1, max>}} $matches */
function foo(array $matches): string
{
	return '';
}

/** @param array<int, array{string, int}> $matches */
function bar(array $matches): string
{
	return '';
}

/** @param array<int> $matches */
function baz(array $matches): string
{
	return '';
}

function (string $s): void {
	preg_replace_callback(
		'/(foo)?(bar)?(baz)?/',
		foo(...),
		$s,
		-1,
		$count,
		PREG_OFFSET_CAPTURE
	);

	preg_replace_callback(
		'/(foo)?(bar)?(baz)?/',
		bar(...),
		$s,
		-1,
		$count,
		PREG_OFFSET_CAPTURE
	);

	preg_replace_callback(
		'/(foo)?(bar)?(baz)?/',
		baz(...),
		$s,
		-1,
		$count,
		PREG_OFFSET_CAPTURE
	);
};
