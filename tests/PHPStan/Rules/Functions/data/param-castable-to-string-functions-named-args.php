<?php declare(strict_types = 1); // lint >= 8.0

namespace ParamCastableToStringFunctionsNamedArgs;

function invalidUsages()
{
	array_combine(values: [['b']], keys: [['a']]);
	$arr1 = [['a']];
	array_fill_keys(value: 5, keys: $arr1);
}

function wrongNumberOfArguments(): void
{
	array_combine(values: [[5]]);
	array_fill_keys(value: [5]);
}

function validUsages()
{
	array_combine(values: [['b']], keys: ['a']);
	$arr1 = ['a'];
	array_fill_keys(value: 5, keys: $arr1);
}
