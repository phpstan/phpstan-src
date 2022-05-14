<?php

namespace ReadOnlyPropertyPhpDoc;

class Foo
{

	/**
	 * @var int
	 * @readonly
	 */
	private $foo;
	/** @var readonly */
	private $bar;
	/**
	 * @var int
	 * @readonly
	 */
	private $baz = 0;

}
