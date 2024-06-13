<?php declare(strict_types = 1); // lint >= 8.1

namespace SortParamCastableToStringFunctionsEnum;

enum FooEnum
{
	case A;
}

function invalidUsages():void
{
	array_unique(['a', FooEnum::A]);
	$arr1 = [FooEnum::A];
	sort($arr1, SORT_STRING);
	rsort($arr1, SORT_LOCALE_STRING);
	asort($arr1, SORT_STRING | SORT_FLAG_CASE);
	arsort($arr1, SORT_LOCALE_STRING | SORT_FLAG_CASE);
}

function validUsages(): void
{
	$arr = [FooEnum::A, 1];
	array_unique($arr, SORT_REGULAR);
	sort($arr, SORT_REGULAR);
	rsort($arr, 128);
}
