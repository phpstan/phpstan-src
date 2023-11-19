<?php

namespace Bug10153;

trait MyTrait
{
	abstract public static function drc(): self;
}

class MyClass
{
	use MyTrait;

	public static function drc(): self
	{
		return new self();
	}
}

class MyClass2
{
	use MyTrait;

	public static function drc(): ?self
	{
		return new self();
	}
}
