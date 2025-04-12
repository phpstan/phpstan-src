<?php declare(strict_types=1);

namespace RememberedConstructorScope;

use LogicException;

class HelloWorld
{
	public function dooFoo(): void
	{
		if (REMEMBERED_FOO === '3') {

		}
	}

	public function returnFoo(): string
	{
		return REMEMBERED_FOO;
	}

	static public function staticFoo(): void
	{
		echo REMEMBERED_FOO; // should error, as can be invoked without instantiation
	}

	public function __construct()
	{
		if (!defined('REMEMBERED_FOO')) {
			throw new LogicException();
		}
		if (!is_string(REMEMBERED_FOO)) {
			throw new LogicException();
		}
	}

	static public function staticFoo2(): void
	{
		echo REMEMBERED_FOO; // should error, as can be invoked without instantiation
	}

	public function returnFoo2(): string
	{
		return REMEMBERED_FOO;
	}
}


class AnotherClass {
	public function myFoo(): string
	{
		return REMEMBERED_FOO; // should error, because this class should not share the __constructor() scope with HelloWorld-class above.
	}
}

class AnotherClassWithOwnConstructor {
	public function __construct()
	{
		if (!defined('ANOTHER_REMEMBERED_FOO')) {
			throw new LogicException();
		}
	}

	public function myFoo(): void
	{
		echo REMEMBERED_FOO; // should error, because this class should not share the __constructor() scope with HelloWorld-class above.

		echo ANOTHER_REMEMBERED_FOO;
	}
}

class MyClassWithDefine {
	public function __construct() {
		define('XYZ', '123');
	}

	public function doFoo(): void {
		echo XYZ;
	}
}

class StaticMethodScopeNotRemembered {
	static public function init() {
		define('XYZ22', '123');
	}

	public function doFoo(): void {
		echo XYZ22; // error because defined in static method
		echo XYZ; // error, because should not mix scope of __constructor() scope of different class
	}
}

class InstanceMethodScopeNotRemembered {
	public function init() {
		define('XYZ33', '123');
	}

	public function doFoo(): void {
		echo XYZ33; // error because defined in non-constructor method
	}
}
