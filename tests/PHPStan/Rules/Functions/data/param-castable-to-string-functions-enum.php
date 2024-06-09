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
	sort($arr1);
	rsort($arr1);
	asort($arr1);
	arsort($arr1);
	natsort($arr1);
	natcasesort($arr1);
	array_count_values($arr1);
	array_fill_keys($arr1, 5);


	implode(',', [FooEnum::A]);
}
