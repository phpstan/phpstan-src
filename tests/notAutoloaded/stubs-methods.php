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

interface InterfaceWithStubPhpDoc
{

	/**
	 * @return int
	 */
	public function doFoo();

}

class ClassExtendingInterfaceWithStubPhpDoc implements InterfaceWithStubPhpDoc
{

}

class AnotherClassExtendingInterfaceWithStubPhpDoc implements InterfaceWithStubPhpDoc
{

}

interface InterfaceWithStubPhpDoc2
{

	/**
	 * @return int
	 */
	public function doFoo();

}

class YetAnotherFoo
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

class YetYetAnotherFoo
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

trait StubbedTrait
{
	/**
	 * @param int $int
	 *
	 * @return void
	 */
	public function doFoo($int)
	{

	}
}
