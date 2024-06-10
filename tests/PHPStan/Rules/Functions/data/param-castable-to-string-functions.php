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

function invalidUsages(): void
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
	sort($arr1, SORT_STRING);
	sort($arr2, SORT_STRING);
	rsort($arr1, SORT_STRING);
	asort($arr1, SORT_STRING);
	arsort($arr1, SORT_STRING);
	natsort($arr1);
	natcasesort($arr1);
	array_count_values($arr1);
	array_fill_keys($arr1, 5);

	sort($arr1, SORT_LOCALE_STRING);
	rsort($arr1, SORT_STRING | SORT_FLAG_CASE);
	asort($arr1, SORT_LOCALE_STRING | SORT_FLAG_CASE);

	// SORT_NUMERIC doesn't allow even __toString()
	$arr3 = [new ClassWithToString()];
	arsort($arr3, SORT_NUMERIC);

}

function wrongNumberOfArguments(): void
{
	implode();
	join();
	array_intersect();
	array_intersect_assoc();
	array_diff();
	array_diff_assoc();
	array_unique();
	array_combine();
	sort();
	rsort();
	asort();
	arsort();
	natcasesort();
	natsort();
	array_count_values();
	array_fill_keys();
}

function validUsages(): void
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
	asort($arr1, SORT_STRING);
	arsort($arr1, SORT_LOCALE_STRING);
	natsort($arr1);
	natcasesort($arr1);
	array_count_values($arr1);
	array_fill_keys($arr1, 5);


	array_combine([true, null, 3.14], ['a', 'b', 'c']);
	array_fill_keys([true, null, 3.14], 'a');

	array_intersect(...[[1, 2], [2, 3]]);

	// SORT_REGULAR doesn't cast to string, so these are safe
	$nonStringArray = [[], new ClassWithoutToString()];
	array_unique($nonStringArray, SORT_REGULAR);
	sort($nonStringArray, SORT_REGULAR);
	rsort($nonStringArray, SORT_REGULAR);
	asort($nonStringArray, SORT_REGULAR);
	arsort($nonStringArray, SORT_REGULAR);

	// Apparently, SORT_NUMERIC does allow arrays.
	$numericArr = ['a', true, null, false, 3, 3.14, [new \stdClass()]];
	sort($numericArr, SORT_NUMERIC);
}
