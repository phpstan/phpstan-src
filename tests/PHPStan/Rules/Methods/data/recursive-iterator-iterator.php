<?php

namespace RecursiveIteratorIteratorMixin;

class Foo
{

	public function doFoo(): void
	{
		$it = new \RecursiveDirectoryIterator(__DIR__);
		$it = new \RecursiveIteratorIterator($it);
		foreach ($it as $_) {
			echo $it->getSubPathname();
			echo $it->getSubPathname(1);
		}
	}

}
