<?php

namespace ClassConstantAttribute;

#[MyAttr(self::FOO), MyAttr(self::BAR)]
class Foo
{

	#[MyAttr(self::FOO), MyAttr(self::BAR)]
	private const FOO = 1;

	#[MyAttr(self::FOO), MyAttr(self::BAR)]
	private $fooProp;

	#[MyAttr(self::FOO), MyAttr(self::BAR)]
	public function doFoo(
		#[MyAttr(self::FOO), MyAttr(self::BAR)]
		$test
	): void
	{

	}

}

#[MyAttr(Foo::FOO), MyAttr(Foo::BAR)]
function doFoo(): void
{

}
