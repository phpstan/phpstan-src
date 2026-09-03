<?php declare(strict_types = 1);

namespace Bug15169;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class C
{

	/** @var array{a: int} */
	public array $prop = ['a' => 1];

	/** @return array{a: int} */
	public function info(): array
	{
		return ['a' => 1];
	}

}

/** @return array{a: int} */
function info(): array
{
	return ['a' => 1];
}

function methodCall(C $c): void
{
	assertType('array{a: int}', $c->info());
	assertNativeType('array', $c->info());

	assertType('false', isset($c->info()['zzz']));
	assertNativeType('bool', isset($c->info()['zzz']));

	assertType('true', empty($c->info()['zzz']));
	assertNativeType('bool', empty($c->info()['zzz']));
}

function propertyFetch(C $c): void
{
	assertType('false', isset($c->prop['zzz']));
	assertNativeType('bool', isset($c->prop['zzz']));

	assertType('true', empty($c->prop['zzz']));
	assertNativeType('bool', empty($c->prop['zzz']));
}

function funcCall(): void
{
	assertType('false', isset(info()['zzz']));
	assertNativeType('bool', isset(info()['zzz']));
}

function coalesce(C $c): void
{
	assertType("'default'", $c->info()['zzz'] ?? 'default');
	assertNativeType('mixed~null', $c->info()['zzz'] ?? 'default');
}
