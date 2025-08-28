<?php

namespace NewStaticInAbstractClassStaticMethod;

class Foo
{

	public function doFoo(): void
	{
		new static();
	}

	public static function staticDoFoo(): void
	{
		new static();
	}

}

abstract class Bar
{

	public function doFoo(): void
	{
		new static();
	}

	public static function staticDoFoo(): void
	{
		new static();
	}

}

abstract class FinalConstructFoo
{
	final function __construct() {

	}

	public function doFoo(): void
	{
		new static();
	}

	public static function staticDoFoo(): void
	{
		new static();
	}

}

class Subclass extends FinalConstructFoo {}
