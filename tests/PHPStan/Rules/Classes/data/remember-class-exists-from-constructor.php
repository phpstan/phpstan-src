<?php // lint >= 7.4

namespace RememberClassExistsFromConstructor;

use SomeUnknownClass;
use SomeUnknownInterface;

class UserWithClass
{
	public function __construct(
	) {
		if (!class_exists('SomeUnknownClass')) {
			throw new \LogicException();
		}
	}

	public function doFoo($m): bool
	{
		if ($m instanceof SomeUnknownClass) {
			return false;
		}
		return true;
	}

}

class UserWithInterface
{
	public function __construct(
	) {
		if (!interface_exists('SomeUnknownInterface')) {
			throw new \LogicException();
		}
	}

	public function doFoo($m): bool
	{
		if ($m instanceof SomeUnknownInterface) {
			return false;
		}
		return true;
	}

}
