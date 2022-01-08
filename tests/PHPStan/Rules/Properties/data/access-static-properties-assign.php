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
