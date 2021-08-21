<?php

namespace OverridingPropertyPhpDoc;

class Foo
{

	/** @var array */
	protected $array;

	/** @var array<class-string> */
	protected $arrayClassStrings;

	/** @var string */
	protected $string;

}

class Bar extends Foo
{

	/** @var array<class-string> */
	protected $array;

	/** @var array */
	protected $arrayClassStrings;

	/** @var int */
	protected $string;

}
