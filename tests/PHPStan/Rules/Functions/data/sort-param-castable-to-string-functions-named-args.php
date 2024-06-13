<?php declare(strict_types = 1); // lint >= 8.0

namespace SortParamCastableToStringFunctionsNamedArgs;

function invalidUsages()
{
	array_unique(flags: SORT_STRING, array: [['a'], ['b']]);
	$arr1 = [['a']];
	sort(flags: SORT_STRING, array: $arr1);
	rsort(flags: SORT_STRING, array: $arr1);
	asort(flags: SORT_STRING, array: $arr1);
	arsort(flags: SORT_STRING, array: $arr1);
}

function wrongNumberOfArguments(): void
{
	array_unique(flags: SORT_STRING);
	sort(flags: SORT_STRING);
	rsort(flags: SORT_STRING);
	asort(flags: SORT_STRING);
	arsort(flags: SORT_STRING);
}

function validUsages()
{
	array_unique(flags: SORT_STRING, array: ['a', 'b']);
	$arr1 = ['a'];
	sort(flags: SORT_STRING, array: $arr1);
	rsort(flags: SORT_STRING, array: $arr1);
	asort(flags: SORT_STRING, array: $arr1);
	arsort(flags: SORT_STRING, array: $arr1);
}
