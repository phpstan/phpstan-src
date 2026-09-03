<?php // lint >= 8.1

declare(strict_types = 1);

// Analyse with treatPhpDocTypesAsCertain: false

namespace Bug15169c;

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

function viaMethodFirstClassCallable(C $c): string
{
	$fn = $c->info(...);
	if (!isset($fn()['zzz'])) {
		return 'early';
	}

	return 'A';
}

function viaStaticMethodFirstClassCallable(): string
{
	$fn = C::staticInfo(...);
	if (!isset($fn()['zzz'])) {
		return 'early';
	}

	return 'B';
}

function viaFunctionFirstClassCallable(): string
{
	$fn = info(...);
	if (!isset($fn()['zzz'])) {
		return 'early';
	}

	return 'C';
}

function viaInvokeFirstClassCallable(C $c): string
{
	$fn = $c(...);
	if (!isset($fn()['zzz'])) {
		return 'early';
	}

	return 'D';
}
