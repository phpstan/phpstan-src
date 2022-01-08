<?php // lint >= 7.4

namespace TestAccessProperties;

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
