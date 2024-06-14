<?php

namespace Bug4707TypeInference;

use function PHPStan\Testing\assertType;

interface Foo
{

	/** @param static $p */
	public function doFoo($p): void;

}

class Bar implements Foo
{

	/** @param static $p */
	public function doFoo($p): void
	{
		assertType('static(Bug4707TypeInference\Bar)', $p);
	}

}

class Bar2 implements Foo
{

	public function doFoo($p): void
	{
		assertType('static(Bug4707TypeInference\Bar2)', $p);
	}

}

final class Bar3 implements Foo
{

	/** @param static $p */
	public function doFoo($p): void
	{
		assertType('Bug4707TypeInference\Bar3', $p);
	}

}

final class Bar4 implements Foo
{

	public function doFoo($p): void
	{
		assertType('Bug4707TypeInference\Bar4', $p);
	}

}
