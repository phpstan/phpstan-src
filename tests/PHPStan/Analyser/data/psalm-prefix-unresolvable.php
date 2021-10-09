<?php

namespace PsalmPrefixedTagsWithUnresolvableTypes;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @return array<int, string>
	 * @psalm-return list<string>
	 */
	public function doFoo()
	{
		return [];
	}

	public function doBar(): void
	{
		assertType('array<int<0, max>, string>', $this->doFoo());
	}

	/**
	 * @param Foo $param
	 * @param Foo $param2
	 * @psalm-param foo-bar $param
	 * @psalm-param foo-bar<Test> $param2
	 */
	public function doBaz($param, $param2)
	{
		assertType('PsalmPrefixedTagsWithUnresolvableTypes\Foo', $param);
		assertType('PsalmPrefixedTagsWithUnresolvableTypes\Foo', $param2);
	}

}
