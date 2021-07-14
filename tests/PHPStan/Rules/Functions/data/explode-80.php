<?php

namespace Explode80;

class Foo
{

	/** @param non-empty-string $nonEmptyString */
	public function doFoo(
		string $s,
		string $nonEmptyString
	): void
	{
		explode($s, 'foo');
		explode($nonEmptyString, 'foo');
		explode('', 'foo');
		explode(1, 'foo');
	}

}
