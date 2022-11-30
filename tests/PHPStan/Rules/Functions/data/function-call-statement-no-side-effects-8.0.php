<?php

namespace FunctionCallStatementNoSideEffects;

use PHPStan\TrinaryLogic;

class Foo
{

	public function noEffect(string $url)
	{
		file_get_contents(filename: $url, offset: 1, length: 100);
		file_get_contents($url, length: 100, offset: 1);
		file_get_contents($url, false, null, length: 100);
	}

	public function hasSideEffect(string $s)
	{
		file_get_contents($url, false, stream_context_create(), length: 100);
		file_get_contents($url, context: stream_context_create());
	}

}
