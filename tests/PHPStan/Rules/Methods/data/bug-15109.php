<?php declare(strict_types = 1);

namespace Bug15109;

function doFoo(string $func):void {
	if (PHP_VERSION_ID >= 80400) {
            $ref = \ReflectionMethod::createFromMethodName($func);
        } else {
            $ref = new \ReflectionMethod($func);
        }
}

class Foo
{

}

class MyReflectionMethod extends \ReflectionMethod
{

}

function doBar(\ReflectionMethod $m, Foo $foo, MyReflectionMethod $myM): void
{
	\ReflectionMethod::futureStaticMethod();
	$m->futureMethod();

	if (PHP_VERSION_ID >= 90000) {
		\ReflectionMethod::futureStaticMethod();
		MyReflectionMethod::futureStaticMethod();
		$m->futureMethod();
		$myM->futureMethod();
		Foo::doesNotExist();
		$foo->doesNotExist();
	}

	if (PHP_VERSION_ID < 50000) {
		\ReflectionMethod::pastStaticMethod();
		$m->pastMethod();
		Foo::doesNotExist();
		$foo->doesNotExist();
	}
}
