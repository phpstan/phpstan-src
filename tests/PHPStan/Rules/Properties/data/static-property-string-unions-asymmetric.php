<?php declare(strict_types = 1); // lint >= 8.5

namespace StaticPropertyStringUnionsAsymmetric;

class Foo
{

	public private(set) static int $privateSet = 0;

	public protected(set) static int $protectedSet = 0;

}

function (): void {
	$class = Foo::class;
	$class::$privateSet = 1;
	$class::$protectedSet = 1;
	echo $class::$privateSet;
};
