<?php declare(strict_types = 1);

namespace Bug14393;

class MyClass
{
	public int $i = 10;

	public function doFoo(): void
	{
		var_dump($this->i ?? -1);
		var_dump(isset($this->i));
		var_dump(empty($this->i));
	}
}

class MyClassUninitialized
{
	public int $i;

	public function doFoo(): void
	{
		var_dump($this->i ?? -1);
		var_dump(isset($this->i));
		var_dump(empty($this->i));
	}
}

class MyClassPhpDoc
{
	/** @var int */
	public $i;

	public function doFoo(): void
	{
		var_dump($this->i ?? -1);
		var_dump(isset($this->i));
		var_dump(empty($this->i));
	}
}

class MyClassNullable
{
	public ?int $i;

	public function doFoo(): void
	{
		var_dump($this->i ?? -1);
		var_dump(isset($this->i));
		var_dump(empty($this->i));
	}
}

class MyClassNullableWithDefault
{
	public ?int $i = 10;

	public function doFoo(): void
	{
		var_dump($this->i ?? -1);
		var_dump(isset($this->i));
		var_dump(empty($this->i));
	}
}

class MyClassNullableWithNullDefault
{
	public ?int $i = null;

	public function doFoo(): void
	{
		var_dump($this->i ?? -1);
		var_dump(isset($this->i));
		var_dump(empty($this->i));
	}
}

$o = new MyClass();

var_dump($o->i ?? -1);
var_dump(isset($o->i));
var_dump(empty($o->i));

$o2 = new MyClassUninitialized();

var_dump($o2->i ?? -1);
var_dump(isset($o2->i));
var_dump(empty($o2->i));

$o3 = new MyClassPhpDoc();

var_dump($o3->i ?? -1);
var_dump(isset($o3->i));
var_dump(empty($o3->i));

$o4 = new MyClassNullable();

var_dump($o4->i ?? -1);
var_dump(isset($o4->i));
var_dump(empty($o4->i));

$o5 = new MyClassNullableWithDefault();

var_dump($o5->i ?? -1);
var_dump(isset($o5->i));
var_dump(empty($o5->i));

$o6 = new MyClassNullableWithNullDefault();

var_dump($o6->i ?? -1);
var_dump(isset($o6->i));
var_dump(empty($o6->i));

class MyClassStatic
{
	public static int $i = 10;

	public function doFoo(): void
	{
		var_dump(self::$i ?? -1);
		var_dump(isset(self::$i));
		var_dump(empty(self::$i));
	}
}

class MyClassStaticUninitialized
{
	public static int $i;

	public function doFoo(): void
	{
		var_dump(self::$i ?? -1);
		var_dump(isset(self::$i));
		var_dump(empty(self::$i));
	}
}

class MyClassStaticNullable
{
	public static ?int $i = null;

	public function doFoo(): void
	{
		var_dump(self::$i ?? -1);
		var_dump(isset(self::$i));
		var_dump(empty(self::$i));
	}
}

var_dump(MyClassStatic::$i ?? -1);
var_dump(isset(MyClassStatic::$i));
var_dump(empty(MyClassStatic::$i));

var_dump(MyClassStaticUninitialized::$i ?? -1);
var_dump(isset(MyClassStaticUninitialized::$i));
var_dump(empty(MyClassStaticUninitialized::$i));

var_dump(MyClassStaticNullable::$i ?? -1);
var_dump(isset(MyClassStaticNullable::$i));
var_dump(empty(MyClassStaticNullable::$i));
