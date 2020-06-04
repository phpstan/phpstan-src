<?php

namespace OverridingFinalMethod;

class Foo
{

	final public function doFoo()
	{

	}

	public function doBar()
	{

	}

	public function doBaz()
	{

	}

	protected function doLorem()
	{

	}

}

class Bar extends Foo
{

	public function doFoo()
	{

	}

	private function doBar()
	{

	}

	protected function doBaz()
	{

	}

	private function doLorem()
	{

	}

}
