<?php // lint >= 8.6

namespace DeadCatchPartialFunctionApplication;

class FooException extends \Exception
{

}

class Foo
{

	public function m(string $a, string $b): string
	{
		return $a . $b;
	}

	public static function s(string $a, string $b): string
	{
		return $a . $b;
	}

}

function f(string $a, string $b): string
{
	return $a . $b;
}

/**
 * @throws FooException
 */
function throws(): string
{
	throw new FooException();
}

function (Foo $foo, callable $cb): void {
	try {
		f(throws(), ?);
	} catch (FooException $e) {
	}

	try {
		$foo->m(throws(), ?);
	} catch (FooException $e) {
	}

	try {
		Foo::s(?, throws());
	} catch (FooException $e) {
	}

	try {
		$cb(throws(), ?);
	} catch (FooException $e) {
	}

	try {
		f('a', ?);
	} catch (FooException $e) {
	}
};
