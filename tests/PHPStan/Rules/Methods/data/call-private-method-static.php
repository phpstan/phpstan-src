<?php

namespace CallPrivateMethodThroughStatic;

class Foo
{

	public function doFoo()
	{
		static::doFoo();
		static::nonexistent();
		static::doBar();
	}

	private function doBar()
	{

	}

}

final class Bar
{

	public function doFoo()
	{
		static::doFoo();
		static::nonexistent();
		static::doBar();
	}

	private function doBar()
	{

	}

}
