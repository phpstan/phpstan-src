<?php declare(strict_types = 1);

namespace Bug12799;

use function PHPStan\Testing\assertType;

function setGet(): void
{
	$_GET['x'] = 'b';
}

/** @phpstan-pure */
function pureFunction(): int
{
	return 1;
}

class Setter
{

	public function set(): void
	{
		$_GET['x'] = 'b';
	}

	public static function setStatic(): void
	{
		$_GET['x'] = 'b';
	}

	public function __construct()
	{
		$_GET['x'] = 'b';
	}

}

class PureSetter
{

	/** @phpstan-pure */
	public function set(): int
	{
		return 1;
	}

	/** @phpstan-pure */
	public static function setStatic(): int
	{
		return 1;
	}

	/** @phpstan-pure */
	public function __construct()
	{
	}

}

function viaFunction(): void
{
	$_GET['x'] = 'a';
	assertType("'a'", $_GET['x']);
	setGet();
	assertType('mixed', $_GET['x']);
}

function viaMethod(Setter $s): void
{
	$_GET['x'] = 'a';
	$s->set();
	assertType('mixed', $_GET['x']);
}

function viaStaticMethod(): void
{
	$_GET['x'] = 'a';
	Setter::setStatic();
	assertType('mixed', $_GET['x']);
}

function viaNew(): void
{
	$_GET['x'] = 'a';
	new Setter();
	assertType('mixed', $_GET['x']);
}

function pureCallKeepsNarrowing(): void
{
	$_GET['x'] = 'a';
	strlen('foo');
	assertType("'a'", $_GET['x']);
}

function pureFunctionKeepsNarrowing(): void
{
	$_GET['x'] = 'a';
	pureFunction();
	assertType("'a'", $_GET['x']);
}

function pureMethodKeepsNarrowing(PureSetter $s): void
{
	$_GET['x'] = 'a';
	$s->set();
	assertType("'a'", $_GET['x']);
}

function pureStaticMethodKeepsNarrowing(): void
{
	$_GET['x'] = 'a';
	PureSetter::setStatic();
	assertType("'a'", $_GET['x']);
}

function pureNewKeepsNarrowing(): void
{
	$_GET['x'] = 'a';
	new PureSetter();
	assertType("'a'", $_GET['x']);
}
