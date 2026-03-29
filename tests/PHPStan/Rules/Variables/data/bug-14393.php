<?php declare(strict_types = 1);

namespace Bug14393;

class MyClass
{
	public int $i = 10;
}

class MyClassUninitialized
{
	public int $i;
}

$o = new MyClass();

var_dump($o->i ?? -1);

$o2 = new MyClassUninitialized();

var_dump($o2->i ?? -1);
