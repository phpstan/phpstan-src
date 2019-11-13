<?php

namespace StubsIntegrationTest;

use RecursiveTemplateProblem\Collection;

class Foo
{

	/**
	 * @param int $i
	 * @return string
	 */
	public function doFoo($i)
	{
		return '';
	}

}

class Bar
{

	/**
	 * @param \RecursiveTemplateProblem\Collection<int, Foo> $collection
	 */
	public function doFoo(Collection $collection): void
	{
		$collection->partition(function ($key, $value): bool {
			return true;
		});
	}

}
