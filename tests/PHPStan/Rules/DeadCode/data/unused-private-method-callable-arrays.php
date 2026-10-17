<?php

namespace UnusedPrivateMethodCallableArrays;

class BigContainer
{

	public static function statusKey(): void
	{
	}

}

class Foo
{

	private static function doSelf(): void
	{
	}

	private static function doStatic(): void
	{
	}

	private static function doClassName(): void
	{
	}

	private static function statusKey(): void
	{
	}

	public function run(): void
	{
		$a = [self::class, 'doSelf'];
		$b = [static::class, 'doStatic'];
		$c = [Foo::class, 'doClassName'];

		$data = [BigContainer::class, 'statusKey'];
		$data2 = ['UnusedPrivateMethodCallableArrays\BigContainer', 'statusKey'];
	}

}
