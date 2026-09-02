<?php declare(strict_types = 1);

namespace ArrowFunctionCallArgType;

use function PHPStan\Testing\assertType;

function doFoo(): void
{
	$viaArrow = [];
	array_push($viaArrow, static fn (): int => 1);
	assertType('array{static-Closure(): 1}', $viaArrow);

	$viaClosure = [];
	array_push($viaClosure, static function (): int {
		return 1;
	});
	assertType('array{static-Closure(): 1}', $viaClosure);
}
