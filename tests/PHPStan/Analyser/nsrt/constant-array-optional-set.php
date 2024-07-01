<?php

namespace ConstantArrayOptionalSet;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo()
	{
		$a = [1];
		if (rand(0, 1)) {
			$a[] = 2;
		}
		assertType('array{0: 1, 1?: 2}', $a);
		if (rand(0, 1)) {
			$a[] = 3;
		}
		assertType('array{0: 1, 1?: 2|3, 2?: 3}', $a);
		if (rand(0, 1)) {
			$a[] = 4;
		}
		assertType('array{0: 1, 1?: 2|3|4, 2?: 3|4, 3?: 4}', $a);
		if (rand(0, 1)) {
			$a[] = 5;
		}
		assertType('array{0: 1, 1?: 2|3|4|5, 2?: 3|4|5, 3?: 4|5, 4?: 5}', $a);
	}

	public function doBar()
	{
		$a = [1];
		if (rand(0, 1)) {
			$a[] = 2;
		}
		assertType('array{0: 1, 1?: 2}', $a);
		if (rand(0, 1)) {
			$a[3] = 3;
		}
		assertType('array{0: 1, 1?: 2, 3: 3}|array{0: 1, 1?: 2}', $a);
		if (rand(0, 1)) {
			$a[] = 4;
		}
		assertType('array{0: 1, 1?: 2, 3: 3, 4?: 4}|array{0: 1, 1?: 2|4, 2?: 4}', $a);
		if (rand(0, 1)) {
			$a[] = 5;
		}
		assertType('array{0: 1, 1?: 2, 3: 3, 4?: 4|5, 5?: 5}|array{0: 1, 1?: 2|4|5, 2?: 4|5, 3?: 5}', $a);
	}

}

class Bar
{

	/**
	 * @param non-empty-list<int>|int $nextAutoIndexes
	 * @return void
	 */
	public function doFoo($nextAutoIndexes)
	{
		assertType('non-empty-list<int>|int', $nextAutoIndexes);
		if (is_int($nextAutoIndexes)) {
			assertType('int', $nextAutoIndexes);
		} else {
			assertType('non-empty-list<int>', $nextAutoIndexes);
		}
		assertType('non-empty-list<int>|int', $nextAutoIndexes);
	}

	/**
	 * @param non-empty-list<int>|int $nextAutoIndexes
	 * @return void
	 */
	public function doBar($nextAutoIndexes)
	{
		assertType('non-empty-list<int>|int', $nextAutoIndexes);
		if (is_int($nextAutoIndexes)) {
			$nextAutoIndexes = [$nextAutoIndexes];
			assertType('array{int}', $nextAutoIndexes);
		} else {
			assertType('non-empty-list<int>', $nextAutoIndexes);
		}
		assertType('non-empty-list<int>', $nextAutoIndexes);
	}

}

class Baz
{

	public function doFoo()
	{
		$conditionalArray = [1, 1, 1];
		if (doFoo()) {
			$conditionalArray[] = 2;
			$conditionalArray[] = 3;
		}

		assertType('array{1, 1, 1, 2, 3}|array{1, 1, 1}', $conditionalArray);

		$unshiftedConditionalArray = $conditionalArray;
		array_unshift($unshiftedConditionalArray, 'lorem', new \stdClass());
		assertType("array{'lorem', stdClass, 1, 1, 1, 2, 3}|array{'lorem', stdClass, 1, 1, 1}", $unshiftedConditionalArray);

		assertType('array{1, 1, 1, 1, 1, 2, 3}|array{1, 1, 1, 1, 1}|array{1, 1, 1, 2, 3, 2, 3}|array{1, 1, 1, 2, 3}', $conditionalArray + $unshiftedConditionalArray);
		assertType("array{'lorem', stdClass, 1, 1, 1, 2, 3}|array{'lorem', stdClass, 1, 1, 1}", $unshiftedConditionalArray + $conditionalArray);

		$conditionalArray[] = 4;
		assertType('array{1, 1, 1, 2, 3, 4}|array{1, 1, 1, 4}', $conditionalArray);
	}

}
