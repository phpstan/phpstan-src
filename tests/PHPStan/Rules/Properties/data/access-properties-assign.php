<?php

namespace TestAccessPropertiesAssign;

class AccessPropertyWithDimFetch
{

	public function doFoo()
	{
		$this->foo['foo'] = 'test';
	}

	public function doBar()
	{
		$this->foo = 'test';
	}

}
