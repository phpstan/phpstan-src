<?php // lint >= 8.1

declare(strict_types = 1);

// The native flavour of the first-class callable nodes -
// MethodCallableNodeHandler, StaticMethodCallableNodeHandler,
// FunctionCallableNodeHandler - and of calling the resulting closure.

namespace Bug15169Callables;

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

function firstClassCallables(C $c): void
{
	assertType('Closure(): array{a: int}', $c->info(...));
	assertNativeType('Closure(): array', $c->info(...));

	assertType('Closure(): array{a: int}', C::staticInfo(...));
	assertNativeType('Closure(): array', C::staticInfo(...));

	assertType('Closure(): array{a: int}', info(...));
	assertNativeType('Closure(): array', info(...));

	// a first-class callable over a callable value resolves through
	// __invoke's own signature
	assertType('Closure(): array{a: int}', $c(...));
	assertNativeType('Closure(): array', $c(...));
}

function callingAFirstClassCallable(C $c): void
{
	$fn = $c->info(...);
	assertType('array{a: int}', $fn());
	assertNativeType('array', $fn());

	assertType('false', isset($fn()['zzz']));
	assertNativeType('bool', isset($fn()['zzz']));

	$staticFn = C::staticInfo(...);
	assertType('array{a: int}', $staticFn());
	assertNativeType('array', $staticFn());

	$funcFn = info(...);
	assertType('array{a: int}', $funcFn());
	assertNativeType('array', $funcFn());

	$invokeFn = $c(...);
	assertType('array{a: int}', $invokeFn());
	assertNativeType('array', $invokeFn());
}
