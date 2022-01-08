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

class AssignOpNonexistentProperty
{

	public function doFoo()
	{
		$this->flags |= 1;
	}

	public function doBar()
	{
		$this->flags ??= 2;
	}

}
