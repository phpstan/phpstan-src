<?php

class ExtendsClassWithUnknownPropertyType extends ClassWithUnknownPropertyType
{

	/** @var self */
	private $foo;

	public function doFoo(): void
	{
		$this->foo->foo();
	}

	public function setFoo(self $foo): void
	{
		$this->foo = $foo;
	}

}
