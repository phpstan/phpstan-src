<?php

namespace EmptyRule;

class Foo
{

	public function doFoo()
	{
		$a = [];
		if (rand(0, 1)) {
			$a[0] = rand(0,1) ? true : false;
			$a[1] = false;
		}

		$a[2] = rand(0,1) ? true : false;
		$a[3] = false;
		$a[4] = true;

		empty($a[0]);
		empty($a[1]);
		empty($a['nonexistent']);
		empty($a[2]);
		empty($a[3]);
		empty($a[4]);
	}

	public function doBar()
	{
		$a = [
			'',
			'0',
			'foo',
			rand(0, 1) ? '' : 'foo',
		];
		empty($a[0]);
		empty($a[1]);
		empty($a[2]);
		empty($a[3]);
	}

}
