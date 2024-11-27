<?php

namespace ListType;

use function PHPStan\Testing\assertType;

class Foo
{
	/** @param list $list */
	public function directAssertion($list): void
	{
		assertType('list<mixed>', $list);
	}

	/** @param list $list */
	public function directAssertionParamHint(array $list): void
	{
		assertType('list<mixed>', $list);
	}

	/** @param list $list */
	public function directAssertionNullableParamHint(array $list = null): void
	{
		assertType('list<mixed>|null', $list);
	}

	/** @param list<\DateTime> $list */
	public function directAssertionObjectParamHint($list): void
	{
		assertType('list<DateTime>', $list);
	}

	public function withoutGenerics(): void
	{
		/** @var list $list */
		$list = [];
		$list[] = '1';
		$list[] = true;
		$list[] = new \stdClass();
		assertType('non-empty-list<mixed>', $list);
	}


	public function withMixedType(): void
	{
		/** @var list<mixed> $list */
		$list = [];
		$list[] = '1';
		$list[] = true;
		$list[] = new \stdClass();
		assertType('non-empty-list<mixed>', $list);
	}

	public function withObjectType(): void
	{
		/** @var list<\DateTime> $list */
		$list = [];
		$list[] = new \DateTime();
		assertType('non-empty-list<DateTime>', $list);
	}

	/** @return list<scalar> */
	public function withScalarGoodContent(): void
	{
		/** @var list<scalar> $list */
		$list = [];
		$list[] = '1';
		$list[] = true;
		assertType('non-empty-list<bool|float|int|string>', $list);
	}

	public function withNumericKey(): void
	{
		/** @var list $list */
		$list = [];
		$list[] = '1';
		$list['1'] = true;
		assertType('non-empty-array<int<0, max>, mixed>&hasOffsetValue(1, true)', $list);
	}

	public function withFullListFunctionality(): void
	{
		// These won't output errors for now but should when list type will be fully implemented
		/** @var list $list */
		$list = [];
		assertType('list<mixed>', $list);
		$list[] = '1';
		assertType('non-empty-list<mixed>', $list);
		$list[] = '2';
		assertType('non-empty-list<mixed>', $list);
		unset($list[0]);//break list behaviour
		assertType('array<int<1, max>, mixed>', $list);

		/** @var list $list2 */
		$list2 = [];
		assertType('list<mixed>', $list2);
		$list2[2] = '1';//Most likely to create a gap in indexes
		assertType('non-empty-array<int<0, max>, mixed>&hasOffsetValue(2, \'1\')', $list2);
	}

	/** @param list<int> $list */
	public function testUnset(array $list): void
	{
		assertType('list<int>', $list);
		unset($list[2]);
		assertType('array<int<0, 1>|int<3, max>, int>', $list);
	}

	/** @param list<int> $list */
	public function testSetOffsetExplicitlyWithoutGap(array $list): void
	{
		assertType('list<int>', $list);
		$list[0] = 17;
		assertType('non-empty-list<int>&hasOffsetValue(0, 17)', $list);
		$list[1] = 19;
		assertType('non-empty-list<int>&hasOffsetValue(0, 17)&hasOffsetValue(1, 19)', $list);
		$list[0] = 21;
		assertType('non-empty-list<int>&hasOffsetValue(0, 21)&hasOffsetValue(1, 19)', $list);
	}

	/** @param list<int> $list */
	public function testSetOffsetExplicitlyWithGap(array $list): void
	{
		assertType('list<int>', $list);
		$list[0] = 17;
		assertType('non-empty-list<int>&hasOffsetValue(0, 17)', $list);
		$list[2] = 21;
		assertType('non-empty-array<int<0, max>, int>&hasOffsetValue(0, 17)&hasOffsetValue(2, 21)', $list);
	}

}
