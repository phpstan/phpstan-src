<?php

namespace NonEmptyArray;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param non-empty-array $array
	 * @param non-empty-list $list
	 * @param non-empty-array<int, string> $arrayOfStrings
	 * @param non-empty-list<\stdClass> $listOfStd
	 * @param non-empty-list<\stdClass> $listOfStd2
	 * @param non-empty-list<string, \stdClass> $invalidList
	 */
	public function doFoo(
		array $array,
		array $list,
		array $arrayOfStrings,
		array $listOfStd,
		$listOfStd2,
		array $invalidList,
		$invalidList2
	): void
	{
		assertType('non-empty-array', $array);
		assertType('non-empty-list<mixed>', $list);
		assertType('non-empty-array<int, string>', $arrayOfStrings);
		assertType('non-empty-list<stdClass>', $listOfStd);
		assertType('non-empty-list<stdClass>', $listOfStd2);
		assertType('array', $invalidList);
		assertType('mixed', $invalidList2);
	}

	/**
	 * @param non-empty-array $array
	 * @param non-empty-list $list
	 * @param non-empty-array<string> $stringArray
	 */
	public function arrayFunctions($array, $list, $stringArray): void
	{
		assertType('non-empty-array', array_combine($array, $array));
		assertType('non-empty-array', array_combine($list, $list));

		assertType('non-empty-array', array_merge($array));
		assertType('non-empty-array', array_merge([], $array));
		assertType('non-empty-array', array_merge($array, []));
		assertType('non-empty-array', array_merge($array, $array));

		assertType('non-empty-array', array_replace($array));
		assertType('non-empty-array', array_replace([], $array));
		assertType('non-empty-array', array_replace($array, []));
		assertType('non-empty-array', array_replace($array, $array));

		assertType('non-empty-array<(int|string)>', array_flip($array));
		assertType('non-empty-array<string, (int|string)>', array_flip($stringArray));
	}
}
