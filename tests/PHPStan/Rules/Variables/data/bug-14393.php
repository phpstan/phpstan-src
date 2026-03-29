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

class MyClassPhpDoc
{
	/** @var int */
	public $i;

	public function doFoo(): void
	{
		var_dump($this->i ?? -1);
	}
}

class MyClassNullable
{
	public ?int $i;

	public function doFoo(): void
	{
		var_dump($this->i ?? -1);
	}
}

class MyClassNullableWithDefault
{
	public ?int $i = 10;

	public function doFoo(): void
	{
		var_dump($this->i ?? -1);
	}
}

class MyClassNullableWithNullDefault
{
	public ?int $i = null;

	public function doFoo(): void
	{
		var_dump($this->i ?? -1);
	}
}

$o = new MyClass();

var_dump($o->i ?? -1);

$o2 = new MyClassUninitialized();

var_dump($o2->i ?? -1);

$o3 = new MyClassPhpDoc();

var_dump($o3->i ?? -1);

$o4 = new MyClassNullable();

var_dump($o4->i ?? -1);

$o5 = new MyClassNullableWithDefault();

var_dump($o5->i ?? -1);

$o6 = new MyClassNullableWithNullDefault();

var_dump($o6->i ?? -1);
