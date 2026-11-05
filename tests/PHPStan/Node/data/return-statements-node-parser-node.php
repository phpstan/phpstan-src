<?php

namespace ReturnStatementsNodeParserNode;

class Foo
{

	public function doFoo(): ?int
	{
		return 1;
	}

	public function doBar()
	{
		$f = static fn (): int => 1;
	}

}

function doBaz(): int
{
	return 2;
}
