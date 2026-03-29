<?php declare(strict_types = 1);

namespace Bug14393;

class MyClass
{
	public int $i = 10;

	public function doFoo(): void
	{
		var_dump($this->i ?? -1);
	}
}

class MyClassUninitialized
{
	public int $i;

	public function doFoo(): void
	{
		var_dump($this->i ?? -1);
	}
}

$o = new MyClass();

var_dump($o->i ?? -1);

$o2 = new MyClassUninitialized();

var_dump($o2->i ?? -1);
