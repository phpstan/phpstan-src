<?php

namespace EnumSanity;

enum EnumWithAbstractMethod
{
	abstract function foo();
}

enum EnumWithConstructorAndDestructor
{
	public function __construct()
	{}

	public function __destruct()
	{}
}
