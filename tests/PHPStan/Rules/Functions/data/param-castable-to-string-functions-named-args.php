<?php declare(strict_types = 1); // lint >= 8.0

namespace ParamCastableToStringFunctionsNamedArgs;

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
	array_unique(flags: SORT_STRING, array: [['a'], ['b']]);
	array_combine(values: [['b']], keys: [['a']]);
	$arr1 = [['a']];
	sort(flags: SORT_REGULAR, array: $arr1);
	rsort(flags: SORT_REGULAR, array: $arr1);
	asort(flags: SORT_REGULAR, array: $arr1);
	arsort(flags: SORT_REGULAR, array: $arr1);
	array_fill_keys(value: 5, keys: $arr1);
	// implode weirdness
	implode(array: [['a']], separator: ',');
	implode(separator: [['a']]);
	implode(',', array: [['a']]);
	implode(separator: ',', array: [['']]);
}

function validUsages()
{
	array_unique(flags: SORT_STRING, array: ['a', 'b']);
	array_combine(values: [['b']], keys: ['a']);
	$arr1 = ['a'];
	sort(flags: SORT_REGULAR, array: $arr1);
	rsort(flags: SORT_REGULAR, array: $arr1);
	asort(flags: SORT_REGULAR, array: $arr1);
	arsort(flags: SORT_REGULAR, array: $arr1);
	array_fill_keys(value: 5, keys: $arr1);
}
