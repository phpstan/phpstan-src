<?php

namespace ArrayDimFetchCast;

class Foo
{

	public function doFoo(array $a): void
	{
		$partiallyCast = rand(0,1) ? '10' : 10;
		echo $a['a'];
		echo $a['+1'];
		echo $a['1']; // cast to 1
		echo $a[null]; // cast to ''
		echo $a[2.5]; // cast to 2
		echo $a['1.2'];
		echo $a[true]; // cast to 1
		echo $a[false]; // cast to 0
		echo $a['08'];
		echo $a[$partiallyCast]; // one part of the union is cast to 10
	}

	public function doBar($mixed): void
	{
		echo $mixed['a'];
		echo $mixed['1'];
	}

}
