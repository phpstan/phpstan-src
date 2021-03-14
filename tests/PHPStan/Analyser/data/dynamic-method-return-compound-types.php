<?php

namespace DynamicMethodReturnCompoundTypes;

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
		die;
	}

}
