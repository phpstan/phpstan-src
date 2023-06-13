<?php

declare(strict_types=1);

namespace Bug6605;

class X {
	public static function doFoo(): void
	{
		$x = new X;
		$x['invalidoffset'][0] = [
			'foo' => 'bar'
		];

		$arr = ['a' => ['b' => [5]]];
		var_dump($arr['invalid']['c']);
		var_dump($arr['a']['invalid']);
	}
}
