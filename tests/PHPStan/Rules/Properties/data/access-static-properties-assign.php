<?php

namespace TestAccessStaticPropertiesAssign;

class AccessStaticPropertyWithDimFetch
{

	public function doFoo()
	{
		self::$foo['foo'] = 'test';
	}

	public function doBar()
	{
		self::$foo = 'test';
	}

}

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
