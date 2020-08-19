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

	public function getProp3()
	{
		return $this->prop3;
	}

}

class ClassUsingTrait
{

	use FooTrait;

	private $prop3;

	public function __construct(string $prop3)
	{
		$this->prop3 = $prop3;
	}

}
