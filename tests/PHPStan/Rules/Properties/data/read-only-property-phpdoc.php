<?php

namespace ReadOnlyPropertyPhpDoc;

class Foo
{

	/**
	 * @readonly
	 * @var int
	 */
	private $foo;

	/** @readonly */
	private $bar;

	/**
	 * @readonly
	 * @var int
	 */
	private $baz = 0;

}
