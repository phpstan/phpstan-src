<?php declare(strict_types = 1);

// The native flavour of every call-like handler: MethodCallHandler,
// StaticCallHandler, FuncCallHandler (named, and a callable value) and
// NewHandler.

namespace Bug15169Calls;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class C
{

	/** @return array{a: int} */
	public function info(): array
	{
		return ['a' => 1];
	}

	/** @return array{a: int} */
	public static function staticInfo(): array
	{
		return ['a' => 1];
	}

	/** @return array{a: int} */
	public function __invoke(): array
	{
		return ['a' => 1];
	}

}

/** @return array{a: int} */
function info(): array
{
	return ['a' => 1];
}

/** @template T */
class Generic
{

	/** @var T */
	public $value;

	/** @param T $value */
	public function __construct($value)
	{
		$this->value = $value;
	}

}

function methodCall(C $c): void
{
	assertType('array{a: int}', $c->info());
	assertNativeType('array', $c->info());

	$name = 'info';
	assertType('array{a: int}', $c->$name());
	assertNativeType('array', $c->$name());
}

function staticCall(): void
{
	assertType('array{a: int}', C::staticInfo());
	assertNativeType('array', C::staticInfo());

	$name = 'staticInfo';
	assertType('array{a: int}', C::$name());
	assertNativeType('array', C::$name());
}

function funcCall(C $c): void
{
	assertType('array{a: int}', info());
	assertNativeType('array', info());

	// a call on a callable value goes through FuncCallHandler's
	// $expr->name instanceof Expr branch: __invoke, a callable string and a
	// callable array
	assertType('array{a: int}', $c());
	assertNativeType('array', $c());

	$callableString = 'Bug15169Calls\info';
	assertType('array{a: int}', $callableString());
	assertNativeType('array', $callableString());

	$callableArray = [$c, 'info'];
	assertType('array{a: int}', $callableArray());
	assertNativeType('array', $callableArray());
}

function instantiation(): void
{
	// the template argument is inferred from the phpdoc @param, so it stays
	// unresolved in the native flavour
	assertType('Bug15169Calls\Generic<int>', new Generic(1));
	assertNativeType('Bug15169Calls\Generic<mixed>', new Generic(1));

	assertType('int', (new Generic(1))->value);
	assertNativeType('mixed', (new Generic(1))->value);
}

function issetOverThem(C $c): void
{
	assertType('false', isset($c->info()['zzz']));
	assertNativeType('bool', isset($c->info()['zzz']));

	assertType('false', isset(C::staticInfo()['zzz']));
	assertNativeType('bool', isset(C::staticInfo()['zzz']));

	assertType('false', isset(info()['zzz']));
	assertNativeType('bool', isset(info()['zzz']));

	assertType('false', isset($c()['zzz']));
	assertNativeType('bool', isset($c()['zzz']));
}
