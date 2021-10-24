<?php

namespace DynamicMethodReturnCompoundTypes;

use function PHPStan\Testing\assertType;

interface Collection extends \Traversable
{

	public function getSelf();

}

class Foo
{

	public function getSelf()
	{

	}

	/**
	 * @param Collection|Foo[] $collection
	 * @param Collection|Foo $collectionOrFoo
	 */
	public function doFoo($collection, $collectionOrFoo)
	{
		assertType('DynamicMethodReturnCompoundTypes\Collection', $collection->getSelf());
		assertType('DynamicMethodReturnCompoundTypes\Collection|DynamicMethodReturnCompoundTypes\Foo', $collectionOrFoo->getSelf());
	}

}
