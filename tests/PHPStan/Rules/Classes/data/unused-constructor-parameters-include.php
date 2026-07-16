<?php

namespace UnusedConstructorParametersInclude;

class Foo
{

	public function __construct(
		$usedInIncludedFile
	)
	{
		require_once __DIR__ . '/foo.php';
	}

}

class Bar
{

	public function __construct(
		$usedViaVariableVariable,
		$name
	)
	{
		echo $$name;
	}

}
