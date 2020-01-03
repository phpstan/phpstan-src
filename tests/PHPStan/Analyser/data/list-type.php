<?php

namespace ListType;

use function PHPStan\Analyser\assertType;

class Foo
{
	public function withoutGenerics(): void
	{
		/** @var list $list */
		$list = [];
		$list[] = '1';
		$list[] = true;
		$list[] = new \stdClass();
		assertType('array<int, mixed>', $list);
	}


	public function withMixedType(): void
	{
		/** @var list<mixed> $list */
		$list = [];
		$list[] = '1';
		$list[] = true;
		$list[] = new \stdClass();
		assertType('array<int, mixed>', $list);
	}

	public function withObjectType(): void
	{
		/** @var list<\DateTime> $list */
		$list = [];
		$list[] = new \DateTime();
		assertType('array<int, \DateTime>', $list);
	}

	public function withObjectTypeWrongContent(): void
	{
		/** @var list<\DateTime> $list */
		$list = [];
		$list[] = new \DateTime();
		$list[] = false;
		assertType('*error*', $list);//this should fail. Fix type
	}

	public function withScalarTypeWrongContent(): void
	{
		/** @var list<scalar> $list */
		$list = [];
		$list[] = '1';
		$list[] = true;
		$list[] = new \DateTime();
		assertType('*error*', $list);//this should fail. Fix type
	}

	/** @return list<scalar> */
	public function withScalarGoodContent(): void
	{
		/** @var list<scalar> $list */
		$list = [];
		$list[] = '1';
		$list[] = true;
		assertType('array<int, scalar>', $list);
	}

	public function withStringKey(): void
	{
		/** @var list $list */
		$list = [];
		$list[] = '1';
		$list['a'] = true;
		assertType('*error*', $list);//this should fail. Fix type
	}

	public function withNumericKey(): void
	{
		/** @var list $list */
		$list = [];
		$list[] = '1';
		$list['1'] = true;
		assertType('array<int, scalar>', $list);
	}

	public function withWrongGenericsNumber(): void
	{
		/** @var list<int, mixed> $list */
		$list = [];
		$list[] = '1';
		$list[] = true;
		assertType('*error*', $list);//this should fail. Fix type
	}

	public function withFullListFunctionality(): void
	{
		// These won't output errors for now but should when list type will be fully implemented
		/** @var list $list */
		$list = [];
		$list[] = '1';
		$list[] = '2';
		unset($list[0]);//break list behaviour
		assertType('array<int, mixed>', $list);

		/** @var list $list2 */
		$list2 = [];
		$list2[2] = '1';//Most likely to create a gap in indexes
		assertType('array<int, scalar>', $list2);
	}

}
