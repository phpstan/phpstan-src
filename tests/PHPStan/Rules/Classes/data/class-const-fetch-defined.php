<?php declare(strict_types = 1);

namespace ClassConstFetchDefined;

class Foo {}

class HelloWorld
{
	public static function doFoo()
	{
		if (defined('Foo::TEST')) {
			Foo::TEST;
			\Foo::TEST;
			\ClassConstFetchDefined\Foo::TEST;
		} else {
			Foo::TEST;
			\Foo::TEST;
			\ClassConstFetchDefined\Foo::TEST;
		}

		if (defined('\Foo::TEST')) {
			Foo::TEST;
			\Foo::TEST;
			\ClassConstFetchDefined\Foo::TEST;
		} else {
			Foo::TEST;
			\Foo::TEST;
			\ClassConstFetchDefined\Foo::TEST;
		}

		if (defined('ClassConstFetchDefined\Foo::TEST')) {
			Foo::TEST;
			\Foo::TEST;
			\ClassConstFetchDefined\Foo::TEST;
		} else {
			Foo::TEST;
			\Foo::TEST;
			\ClassConstFetchDefined\Foo::TEST;
		}

		if (defined('\ClassConstFetchDefined\Foo::TEST')) {
			Foo::TEST;
			\Foo::TEST;
			\ClassConstFetchDefined\Foo::TEST;
		} else {
			Foo::TEST;
			\Foo::TEST;
			\ClassConstFetchDefined\Foo::TEST;
		}

		if (defined('Foo::TEST') === true) {
			Foo::TEST;
			\Foo::TEST;
			\ClassConstFetchDefined\Foo::TEST;
		} else {
			Foo::TEST;
			\Foo::TEST;
			\ClassConstFetchDefined\Foo::TEST;
		}
	}
}
