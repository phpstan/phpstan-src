<?php

namespace TraitsUnititializedProperty;

class FooClass
{
	use FooTrait;

	public function __construct()
	{
		$this->foo();
		$this->x = 5;
		$this->y = 5;
		$this->z = 5;
	}
}
