<?php declare(strict_types = 1);

namespace ParamCastableToStringFunctions;

class ClassWithoutToString {}
class ClassWithToString
{
	public function __toString(): string
	{
		return 'foo';
	}
}

function invalidUsages()
{
	array_intersect([[]], ['a']);
	array_intersect(['a'], [new ClassWithoutToString()]);
	array_intersect(['a'], [], [new ClassWithoutToString()]);
	array_diff(['a'], [new ClassWithoutToString()]);
	array_diff_assoc(['a'], [new ClassWithoutToString()]);

	array_unique([['a'], ['b']]);
	array_combine([['a']], [['b']]);
	$arr1 = [['a']];
	$arr2 = [new ClassWithoutToString()];
	sort($arr1);
	sort($arr2);
	rsort($arr1);
	asort($arr1);
	arsort($arr1);
	natsort($arr1);
	natcasesort($arr1);
	array_count_values($arr1);
	array_fill_keys($arr1, 5);
	array_flip($arr1);
}

function validUsages()
{
	array_intersect(['a'], ['a']);
	array_intersect(['a'], [new ClassWithToString()]);
	array_intersect(['a'], [], [new ClassWithToString()]);
	array_diff(['a'], [new ClassWithToString()]);
	array_diff_assoc(['a'], [new ClassWithToString()]);

	array_unique(['a', 'b']);
	array_combine(['a'], [['b']]);
	$arr1 = ['a', new ClassWithToString()];
	sort($arr1);
	rsort($arr1);
	asort($arr1);
	arsort($arr1);
	natsort($arr1);
	natcasesort($arr1);
	array_count_values($arr1);
	array_fill_keys($arr1, 5);
	array_flip($arr1);

	array_combine([true, null, 3.14], ['a', 'b', 'c']);
	array_fill_keys([true, null, 3.14], 'a');

	array_intersect(...[[1, 2], [2, 3]]);
}
