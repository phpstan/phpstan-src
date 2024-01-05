<?php declare(strict_types=1); // lint >= 7.4

namespace RequireImplements;

interface MyInterface
{
	public function doSomething(): string;

	static public function doSomethingStatic(): int;
}

/**
 * @phpstan-require-implements MyInterface
 */
trait MyTrait
{
	public string $foo = 'hello';
}

abstract class MyBaseClass implements MyInterface
{
	use MyTrait;
	public string $bar = 'world';

	public function doSomething(): string
	{
		return 'foo';
	}

	static public function doSomethingStatic(): int
	{
		return 1;
	}
}

function getFoo(MyBaseClass $obj): string
{
	echo $obj->bar;
	return $obj->foo;
}

function callFoo(MyBaseClass $obj): string
{
	echo $obj->doesNotExist();
	echo $obj::doesNotExistStatic();
	echo $obj::doSomethingStatic();
	return $obj->doSomething();
}
