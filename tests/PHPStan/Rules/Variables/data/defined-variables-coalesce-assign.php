<?php

namespace DefinedVariablesCoalesceAssign;

class Foo
{

	public function doFoo()
	{
		$a ??= 'foo';
		$b['foo'] ??= 'bar';
	}

	public function doBar()
	{
		$a ??= $b;
	}

}
