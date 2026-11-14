<?php // lint >= 8.1

namespace GetClassWithoutArguments;

class ParentClass
{

}

trait FooTrait
{

	public function traitMethod(): void
	{
		echo get_class();
		echo get_parent_class();
	}

}

class Foo extends ParentClass
{

	use FooTrait;

	public function doFoo(object $object): void
	{
		echo get_class();
		echo \get_class();
		echo get_parent_class();
		$closure = static fn (): string => get_class();

		echo get_class($object);
		echo get_class($this);
		echo get_parent_class($this);
		$callable = get_class(...);
	}

}

class WithoutParent
{

	public function doFoo(): void
	{
		echo get_parent_class();
	}

}

function doFoo(): void
{
	echo get_class();
	echo get_parent_class();
}
