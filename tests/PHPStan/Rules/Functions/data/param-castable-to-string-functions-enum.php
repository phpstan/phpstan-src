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

	array_combine([FooEnum::A], [['b']]);
	$arr1 = [FooEnum::A];
	natsort($arr1);
	natcasesort($arr1);
	array_count_values($arr1);
	array_fill_keys($arr1, 5);
}
