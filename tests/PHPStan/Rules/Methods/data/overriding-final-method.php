<?php

namespace OverridingFinalMethod;

class Foo
{

	final public function doFoo()
	{

	}

}

class Bar extends Foo
{

	public function doFoo()
	{

	}

}
