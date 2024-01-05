<?php declare(strict_types=1); // lint >= 7.4

namespace RequireExtends;

/**
 * Implementors are expected to use MyTrait.
 *
 * A base implementation is provided by MyBaseClass.
 *
 * @phpstan-require-extends MyBaseClass
 */
interface MyInterface
{
}

trait MyTrait
{
	public string $foo = 'hello';
}

abstract class MyBaseClass implements MyInterface
{
	use MyTrait;

	public function doSomething(): string {
		return 'hallo';
	}

	static public function doSomethingStatic(): int {
		return 123;
	}
}

function getFoo(MyInterface $obj): string
{
	echo $obj->bar;
	return $obj->foo;
}


function callFoo(MyInterface $obj): string
{
	echo $obj->doesNotExist();
	echo MyInterface::doesNotExistStatic();
	echo MyInterface::doSomethingStatic();
	return $obj->doSomething();
}
