<?php declare(strict_types = 1);

namespace SortParamCastableToStringFunctions;

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
	array_unique([['a'], ['b']]);
	$arr1 = [['a']];
	$arr2 = [new ClassWithoutToString()];
	sort($arr1, SORT_STRING);
	sort($arr2, SORT_STRING);
	rsort($arr1, SORT_STRING);
	asort($arr1, SORT_STRING);
	arsort($arr1, SORT_STRING);

	sort($arr1, SORT_LOCALE_STRING);
	rsort($arr1, SORT_STRING | SORT_FLAG_CASE);
	asort($arr1, SORT_LOCALE_STRING | SORT_FLAG_CASE);

	// SORT_NUMERIC doesn't allow even __toString()
	$arr3 = [new ClassWithToString()];
	arsort($arr3, SORT_NUMERIC);
	arsort($arr3, rand() ? SORT_NUMERIC : SORT_STRING);
	sort($arr1, rand() ? SORT_STRING : SORT_REGULAR);
	sort($arr1, rand());
}

function wrongNumberOfArguments(): void
{
	array_unique();
	array_combine();
	sort();
	rsort();
	asort();
	arsort();
}

function validUsages(): void
{
	array_unique(['a', 'b']);
	$arr1 = ['a', new ClassWithToString()];
	sort($arr1);
	rsort($arr1);
	asort($arr1, SORT_STRING);
	arsort($arr1, SORT_LOCALE_STRING);
	array_intersect(...[[1, 2], [2, 3]]);

	// SORT_REGULAR doesn't cast to string, so these are safe
	$nonStringArray = [[], new ClassWithoutToString()];
	array_unique($nonStringArray, SORT_REGULAR);
	sort($nonStringArray, SORT_REGULAR);
	rsort($nonStringArray, SORT_REGULAR);
	asort($nonStringArray, SORT_REGULAR);
	arsort($nonStringArray, SORT_REGULAR);
	// It seems that PHP handles invalid flag as SORT_REGULAR
	arsort($nonStringArray, 128);
	array_unique($nonStringArray, 128);

	// Apparently, SORT_NUMERIC does allow arrays.
	$numericArr = ['a', true, null, false, 3, 3.14, [new \stdClass()]];
	sort($numericArr, SORT_NUMERIC);
}
