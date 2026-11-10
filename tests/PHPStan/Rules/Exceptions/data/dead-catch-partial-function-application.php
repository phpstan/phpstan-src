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

function (string $s): void {
	// calling the closure throws what the call of its callee throws
	$decodeThrowing = json_decode(?, flags: JSON_THROW_ON_ERROR);
	try {
		$decodeThrowing($s);
	} catch (\JsonException $e) {
	}

	$decode = json_decode(?, true);
	try {
		$decode($s);
	} catch (\JsonException $e) {
	}
};
