<?php declare(strict_types = 1);

namespace Bug14393;

class MyClass
{
	public int $i = 10;
}

$o = new MyClass();

var_dump($o->i ?? -1);
