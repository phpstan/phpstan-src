<?php

namespace WrongVariableNameVarTagAssignOperator;

class Foo
{

	public function doFoo()
	{
		/** @var int $test */
		$test ??= doFoo();

		/** @var int */
		$test ??= doFoo();
	}

	public function doBar(string $string)
	{
		/** @var int $string */
		$string .= doFoo();

		/** @var int */
		$string .= doFoo();
	}

}

function doFoo(): void
{

}
