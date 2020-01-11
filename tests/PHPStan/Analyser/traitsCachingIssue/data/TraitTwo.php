<?php

namespace TraitsCachingIssue;

use stdClass as Foo;

trait TraitTwo
{

	/**
	 * @return Foo
	 */
	public function doFoo()
	{
		return new Foo();
	}

}
