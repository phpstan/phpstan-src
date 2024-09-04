<?php

namespace AccessStaticProperties;

class AssignOpNonexistentProperty
{

	public function doFoo()
	{
		self::$flags |= 1;
	}

	public function doBar()
	{
		self::$flags ??= 2;
	}

}
