<?php

namespace OptimizedDirectory;

class WithDefineCall
{
	public function doSomething()
	{
		$this->define('DEFINE_THAT_SHOULD_SURVIVE_METHOD_CALL', 'no_define');
	}
}
