<?php declare(strict_types = 1);

namespace Bug15109Properties;

class Foo
{

}

function doFoo(\Exception $exception, Foo $foo): void
{
	echo $exception->futureProperty;
	echo \Exception::$futureStaticProperty;

	if (PHP_VERSION_ID >= 90000) {
		echo $exception->futureProperty;
		echo \Exception::$futureStaticProperty;
		echo $foo->doesNotExist;
		echo Foo::$doesNotExist;
	}
}
