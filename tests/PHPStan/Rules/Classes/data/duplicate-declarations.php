<?php

namespace DuplicateDeclarations;

class Foo{

	public const CONST1 = 1;
	public const CONST1 = 2;

	public const CONST2 = 2, CONST2 = 1;

	public const CONST3 = 1;

	/** @var int */
	public $prop1;
	/** @var int */
	public $prop1;

	/** @var int */
	public $prop2, $prop2;

	/** @var int */
	public $prop3;

	public function func1() : void{}

	public function func1() : int{
		return 1;
	}

	public function func2() : int{
		return 2;
	}
}
