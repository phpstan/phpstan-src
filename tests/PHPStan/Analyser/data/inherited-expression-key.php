<?php

namespace InheritedExpressionKey;

class Foo
{

	public function doFoo(array $items): void
	{
		$foo = null;
		$i = 0;
		foreach ($items as $item) {
			$foo = new Foo();

			$foo && $i++;
		}
	}

}
