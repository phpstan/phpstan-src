<?php // lint >= 8.6

namespace PartialFunctionApplicationDefinedVariables;

class Foo
{

	public function doFoo(string $a, string $b): string
	{
		return $a . $b;
	}

	public static function doBar(string $a, string $b): string
	{
		return $a . $b;
	}

}

function (Foo $foo): void {
	str_replace($undefined, '-', ?);
	$foo->doFoo($undefined, ?);
	Foo::doBar(?, $undefined);

	str_replace($a = ' ', '-', ?);
	echo $a;
	$foo->doFoo($b = 'b', ?);
	echo $b;
	Foo::doBar(?, $c = 'c');
	echo $c;
	$foo->doFoo(...);
	strlen(?);
};
