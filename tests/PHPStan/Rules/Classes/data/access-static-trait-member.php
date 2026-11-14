<?php // lint >= 8.1

namespace AccessStaticTraitMember;

trait FooTrait
{

	public static int $counter = 0;

	public static function create(): void
	{
	}

	public function instanceMethod(): void
	{
		FooTrait::create();
		FooTrait::$counter++;
		self::create();
		static::create();
		self::$counter++;
		static::$counter++;
	}

}

class Foo
{

	use FooTrait;

}

class Bar extends Foo
{

	public function doBar(): void
	{
		parent::create();
		parent::$counter++;
	}

}

/**
 * @param class-string<Foo> $className
 */
function doFoo(string $className): void
{
	FooTrait::create();
	echo FooTrait::$counter;
	FooTrait::$counter = 5;
	$callable = FooTrait::create(...);
	$traitName = 'AccessStaticTraitMember\FooTrait';
	$traitName::create();
	$traitName::$counter++;

	Foo::create();
	Foo::$counter++;
	$className::create();
	$className::$counter++;
}
