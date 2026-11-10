<?php declare(strict_types = 1); // lint >= 8.0

namespace StaticPropertyStringUnionsAssign;

class Foo
{

	public static int $count = 0;

	private static int $secret = 0;

}

class Baz
{

}

trait SomeTrait
{

	public static int $traitProperty = 0;

}

class Runner
{

	public function writeToMissingProperty(): void
	{
		$class = Baz::class;
		$class::$missing = 1;
	}

	public function incrementMissingProperty(): void
	{
		$class = Baz::class;
		$class::$missing++;
	}

	public function coalesceAssignMissingProperty(): void
	{
		$class = Baz::class;
		$class::$missing ??= 1;
	}

	public function writeToPrivateProperty(): void
	{
		$class = Foo::class;
		$class::$secret = 1;
	}

	public function writeToUnion(bool $flag): void
	{
		$class = $flag ? Foo::class : Baz::class;
		$class::$count = 1;
	}

	public function writeToExistingProperty(): void
	{
		$class = Foo::class;
		$class::$count = 1;
	}

	public function writeThroughTrait(): void
	{
		$trait = SomeTrait::class;
		$trait::$traitProperty = 1;
	}

}
