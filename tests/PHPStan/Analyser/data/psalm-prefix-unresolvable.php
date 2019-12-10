<?php

namespace PsalmPrefixedTagsWithUnresolvableTypes;

use function PHPStan\Analyser\assertType;

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
		assertType('array<int, string>', $this->doFoo());
	}

}
