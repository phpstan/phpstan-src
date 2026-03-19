<?php

namespace LiteralArrayKeyCast;

class Foo
{

	public function doFoo(): void
	{
		$partiallyCast = rand(0,1) ? '10' : 10;
		$a = [
			'a' => 1,
			'+1' => 2,
			'1' => 3, // cast to 1
			null => 4, // cast to ''
			2.5 => 5, // cast to 2
			'1.2' => 6,
			true => 7, // cast to 1
			false => 8, // cast to 0
			'08' => 9,
			$partiallyCast => 10, // one part of the union is cast to 10
		];
	}

}
