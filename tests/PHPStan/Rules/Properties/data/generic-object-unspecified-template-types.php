<?php

namespace GenericObjectUnspecifiedTemplateTypes;

class Foo
{

	/** @var \ArrayObject<int, string> */
	private $obj;

	public function __construct()
	{
		$this->obj = new \ArrayObject();
	}

}
