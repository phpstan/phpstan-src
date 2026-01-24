<?php

namespace MaybeNotCallable;

class Bar
{
	private function doFoo()
	{
		echo 'yes';
	}

	public function doBar()
	{
		$cb = [rand(0,1) ? 'MaybeNotCallable\Bar' : $this, 'doFoo'];
		$cb();
	}
}
