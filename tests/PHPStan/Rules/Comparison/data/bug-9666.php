<?php declare(strict_types=1);

namespace Bug9666;

class A
{
	/**
	 * @return array<int,bool>
	 */
	function b()
	{
		return [];
	}
}

function doFoo() {
	$a = new A();
	$b = $a->b();
	$c = null;
	if ($b && is_bool($c = reset($b))) {
		//
	}
}

