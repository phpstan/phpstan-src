<?php

namespace ExplainUnresolvableMethodParameter;

class Foo
{

	/**
	 * @param array{foo: int}&array{bar: string} $a
	 */
	public function doFoo(array $a): void
	{

	}

}
