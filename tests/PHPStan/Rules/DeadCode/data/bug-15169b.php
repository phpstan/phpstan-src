<?php declare(strict_types = 1);

// Analyse with treatPhpDocTypesAsCertain: false

namespace Bug15169b;

class C
{

	/** @var array{a: int} */
	public array $prop = ['a' => 1];

	/** @var array{a: int} */
	public static array $staticProp = ['a' => 1];

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

	/** @return array{a: int} */
	public function info(): array
	{
		return ['a' => 1];
	}

	public function viaThisProperty(): string
	{
		if (!isset($this->prop['zzz'])) {
			return 'early';
		}

		return 'A';
	}

	public function viaThisCall(): string
	{
		if (!isset($this->info()['zzz'])) {
			return 'early';
		}

		return 'B';
	}

}

/** @return array{a: int} */
function info(): array
{
	return ['a' => 1];
}

function viaStaticCall(): string
{
	if (!isset(C::staticInfo()['zzz'])) {
		return 'early';
	}

	return 'C';
}

function viaStaticProperty(): string
{
	if (!isset(C::$staticProp['zzz'])) {
		return 'early';
	}

	return 'D';
}

function viaFunctionCall(): string
{
	if (!isset(info()['zzz'])) {
		return 'early';
	}

	return 'E';
}

function viaInvoke(C $c): string
{
	if (!isset($c()['zzz'])) {
		return 'early';
	}

	return 'F';
}

function viaStaticPropertyEmpty(): string
{
	if (empty(C::$staticProp['zzz'])) {
		return 'early';
	}

	return 'G';
}
