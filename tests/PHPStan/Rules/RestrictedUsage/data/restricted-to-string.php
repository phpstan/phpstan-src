<?php

namespace RestrictedUsage;

class FooToString
{

	public function doTest(Nonexistent $c): void
	{
		(string) $c;
		(string) $this;
	}

	public function __toString()
	{
		return 'foo';
	}

	public function doTest2(FooWithoutToString $f)
	{
		(string) $f;
	}

	public function doTest3(?FooToString $f)
	{
		(string) $f;
	}

}

class FooWithoutToString
{

}
