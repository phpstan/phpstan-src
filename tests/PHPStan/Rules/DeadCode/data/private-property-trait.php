<?php

namespace UnusedPropertyTrait;

trait FooTrait
{

	private $prop1;

	private $prop2;

	public function doFoo()
	{
		echo $this->prop1;
	}

	public function setFoo($prop1)
	{
		$this->prop1 = $prop1;
	}

}

class ClassUsingTrait
{

	use FooTrait;

}
