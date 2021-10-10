<?php

namespace ListType;

use function PHPStan\Testing\assertType;

class Foo
{
	/** @param list $list */
	public function directAssertion($list): void
	{
		assertType('array<int<0, max>, mixed>', $list);
	}

	/** @param list $list */
	public function directAssertionParamHint(array $list): void
	{
		assertType('array<int<0, max>, mixed>', $list);
	}

	/** @param list $list */
	public function directAssertionNullableParamHint(array $list = null): void
	{
		assertType('array<int<0, max>, mixed>|null', $list);
	}

	/** @param list<\DateTime> $list */
	public function directAssertionObjectParamHint($list): void
	{
		assertType('array<int<0, max>, DateTime>', $list);
	}

	public function withoutGenerics(): void
	{
		/** @var list $list */
		$list = [];
		assertType('array<int<0, max>, mixed>', $list);
		$list[] = '1';
		$list[] = true;
		$list[] = new \stdClass();
		assertType('array<int<0, max>, mixed>&nonEmpty', $list);
	}


	public function withMixedType(): void
	{
		/** @var list<mixed> $list */
		$list = [];
		$list[] = '1';
		$list[] = true;
		$list[] = new \stdClass();
		assertType('array<int<0, max>, mixed>&nonEmpty', $list);
	}

	public function withObjectType(): void
	{
		/** @var list<\DateTime> $list */
		$list = [];
		$list[] = new \DateTime();
		assertType('array<int<0, max>, DateTime>&nonEmpty', $list);
	}

	/** @return list<scalar> */
	public function withScalarGoodContent(): void
	{
		/** @var list<scalar> $list */
		$list = [];
		$list[] = '1';
		$list[] = true;
		assertType('array<int<0, max>, bool|float|int|string>&nonEmpty', $list);
	}

	public function withNumericKey(): void
	{
		/** @var list $list */
		$list = [];
		$list[] = '1';
		$list['1'] = true;
		assertType('array<int<0, max>, mixed>&nonEmpty', $list);
	}

	public function withFullListFunctionality(): void
	{
		// These won't output errors for now but should when list type will be fully implemented
		/** @var list $list */
		$list = [];
		$list[] = '1';
		$list[] = '2';
		unset($list[0]);//break list behaviour
		assertType('array<int<0, max>, mixed>', $list);

		/** @var list $list2 */
		$list2 = [];
		$list2[2] = '1';//Most likely to create a gap in indexes
		assertType('array<int<0, max>, mixed>&nonEmpty', $list2);
	}

}
