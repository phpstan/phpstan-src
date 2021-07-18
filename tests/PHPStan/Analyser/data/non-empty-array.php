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
		assertType('array&nonEmpty', $array);
		assertType('array<int, mixed>&nonEmpty', $list);
		assertType('array<int, string>&nonEmpty', $arrayOfStrings);
		assertType('array<int, stdClass>&nonEmpty', $listOfStd);
		assertType('array<int, stdClass>&nonEmpty', $listOfStd2);
		assertType('array', $invalidList);
		assertType('mixed', $invalidList2);
	}

	/** @param non-empty-array $nonE */
	public function arrayFunctions($nonE): void
	{
		assertType('array&nonEmpty', array_combine($nonE, $nonE));
	}
}
