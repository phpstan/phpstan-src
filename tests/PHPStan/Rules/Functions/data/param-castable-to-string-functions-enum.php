<?php declare(strict_types = 1); // lint >= 8.1

namespace ParamCastableToStringFunctionsEnum;

enum FooEnum
{
	case A;
}

function invalidUsages()
{
	array_intersect([FooEnum::A], ['a']);
	array_intersect(['a'], [FooEnum::A]);
	array_intersect(['a'], [], [FooEnum::A]);
	array_diff(['a'], [FooEnum::A]);
	array_diff_assoc(['a'], [FooEnum::A]);

	array_unique(['a', FooEnum::A]);
	array_combine([FooEnum::A], [['b']]);
	$arr1 = [FooEnum::A];
	sort($arr1, SORT_STRING);
	rsort($arr1, SORT_LOCALE_STRING);
	asort($arr1, SORT_STRING | SORT_FLAG_CASE);
	arsort($arr1, SORT_LOCALE_STRING | SORT_FLAG_CASE);
	natsort($arr1);
	natcasesort($arr1);
	array_count_values($arr1);
	array_fill_keys($arr1, 5);


	implode(',', [FooEnum::A]);
}
