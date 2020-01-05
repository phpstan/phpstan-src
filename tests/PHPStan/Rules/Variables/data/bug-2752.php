<?php

namespace Bug2572;

class Foo extends \SimpleXMLElement
{

	public function doFoo()
	{
		unset($this[0]);
	}

}
