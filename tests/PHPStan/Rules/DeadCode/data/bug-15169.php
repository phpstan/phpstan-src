<?php declare(strict_types = 1);

// Analyse with treatPhpDocTypesAsCertain: false

namespace Bug15169DeadCode;

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

function viaCall(C $c): string
{
	if (!isset($c->info()['zzz'])) {
		return 'early';
	}

	return 'A';
}

function viaProperty(C $c): string
{
	if (!isset($c->prop['zzz'])) {
		return 'early';
	}

	return 'C';
}

function viaCallEmpty(C $c): string
{
	if (empty($c->info()['zzz'])) {
		return 'early';
	}

	return 'E';
}

function orChain(?C $c): string
{
	if ($c === null || !isset($c->info()['zzz'])) {
		return 'early';
	}

	return 'F';
}

// --- correct on every version: the same check through a variable ---

function viaVariable(C $c): string
{
	$arr = $c->info();
	if (!isset($arr['zzz'])) {
		return 'early';
	}

	return 'B';
}

/** @param array{a: int} $arr */
function viaParam(array $arr): string
{
	if (!isset($arr['zzz'])) {
		return 'early';
	}

	return 'D';
}
