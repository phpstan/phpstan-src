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

enum EnumWithMagicMethods
{
	public function __get()
	{}

	public function __call()
	{}

	public function __callStatic()
	{}

	public function __set()
	{}

	public function __invoke()
	{}
}
