<?php

namespace IssetInTrait;

// Trait where property type differs across classes (should suppress errors)
trait IssetTrait
{
	public function doFoo(): void
	{
		var_dump($this->i ?? -1);
		var_dump(isset($this->i));
		var_dump(empty($this->i));
	}
}

class MyClass
{
	use IssetTrait;

	public int $i = 10;
}

class MyClassNullable
{
	use IssetTrait;

	public ?int $i = null;
}

// Trait where property type is the same in all classes (should still report errors)
trait AlwaysNonNullableTrait
{
	public function doFoo(): void
	{
		var_dump($this->j ?? -1);
		var_dump(isset($this->j));
		var_dump(empty($this->j));
	}
}

class ClassA
{
	use AlwaysNonNullableTrait;

	public int $j = 10;
}

class ClassB
{
	use AlwaysNonNullableTrait;

	public int $j = 20;
}
