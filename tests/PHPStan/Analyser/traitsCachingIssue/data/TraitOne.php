<?php

namespace TraitsCachingIssue;

use stdClass as Foo;

trait TraitOne
{

	/**
	 * @return Foo
	 */
	public function doFoo()
	{
		return new Foo();
	}

}
