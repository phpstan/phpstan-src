<?php declare(strict_types = 1);

namespace Bug15109ClassConstant;

class Foo
{

}

function doFoo(): void
{
	echo \ReflectionProperty::FUTURE_CONSTANT;

	if (PHP_VERSION_ID >= 90000) {
		echo \ReflectionProperty::FUTURE_CONSTANT;
		echo Foo::DOES_NOT_EXIST;
	}
}
