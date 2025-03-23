<?php

namespace NewIsAlwaysFinalVarTagType;

class Foo
{

	/**
	 * @return static
	 */
	public function returnStatic()
	{
		return $this;
	}

}

function (): void {
	$foo = new Foo();

	/** @var Foo $bar */
	$bar = $foo->returnStatic();
};
