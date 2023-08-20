<?php

namespace InstanceOfNamespaceRule;

class Foo
{

	public function foobar()
	{
		if ($this instanceof self) {

		}
	}

}
