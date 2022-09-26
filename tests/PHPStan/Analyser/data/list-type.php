<?php

namespace ListType;

use function PHPStan\Testing\assertType;

class Foo
{
	/** @param list $list */
	public function directAssertion($list): void
	{
		assertType('array<int, mixed>', $list);
	}

	/** @param list $list */
	public function directAssertionParamHint(array $list): void
	{
		assertType('array<int, mixed>', $list);
	}

	/** @param list $list */
	public function directAssertionNullableParamHint(array $list = null): void
	{
		assertType('array<int, mixed>|null', $list);
	}

	/** @param list<\DateTime> $list */
	public function directAssertionObjectParamHint($list): void
	{
		assertType('array<int, DateTime>', $list);
	}

	public function withoutGenerics(): void
	{
		/** @var list $list */
		$list = [];
		$list[] = '1';
		$list[] = true;
		$list[] = new \stdClass();
		assertType('non-empty-array<int, mixed>', $list);
	}


	public function withMixedType(): void
	{
		/** @var list<mixed> $list */
		$list = [];
		$list[] = '1';
		$list[] = true;
		$list[] = new \stdClass();
		assertType('non-empty-array<int, mixed>', $list);
	}

	public function withObjectType(): void
	{
		/** @var list<\DateTime> $list */
		$list = [];
		$list[] = new \DateTime();
		assertType('non-empty-array<int, DateTime>', $list);
	}

	/** @return list<scalar> */
	public function withScalarGoodContent(): void
	{
		/** @var list<scalar> $list */
		$list = [];
		$list[] = '1';
		$list[] = true;
		assertType('non-empty-array<int, bool|float|int|string>', $list);
	}

	public function withNumericKey(): void
	{
		/** @var list $list */
		$list = [];
		$list[] = '1';
		$list['1'] = true;
		assertType('non-empty-array<int, mixed>&hasOffsetValue(1, true)', $list);
	}

	public function withFullListFunctionality(): void
	{
		// These won't output errors for now but should when list type will be fully implemented
		/** @var list $list */
		$list = [];
		$list[] = '1';
		$list[] = '2';
		unset($list[0]);//break list behaviour
		assertType('array<int<min, -1>|int<1, max>, mixed>', $list);

		/** @var list $list2 */
		$list2 = [];
		$list2[2] = '1';//Most likely to create a gap in indexes
		assertType('non-empty-array<int, mixed>&hasOffsetValue(2, \'1\')', $list2);
	}

}
