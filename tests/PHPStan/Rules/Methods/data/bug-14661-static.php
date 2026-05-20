<?php declare(strict_types = 1);

namespace Bug14661Static;

class E
{
	public static function bar(
		?string $x = null,
		?string $y = null,
	): void {}
}

class F
{
	public static function bar(
		?string $y = null,
		?string $x = null,
	): void {}
}

function staticMethodCall(E|F $obj): void
{
	$obj::bar(x: 'value');
	$obj::bar(y: 'value');
	$obj::bar(x: 'v1', y: 'v2');
	$obj::bar(unknown: 'value');
}
