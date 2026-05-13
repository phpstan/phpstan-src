<?php // lint >= 8.0

namespace Bug14605;

use function PHPStan\Testing\assertType;

function testPregMatchOptionalGroupWithCountAndSubstr(string $resource): void {
	if (preg_match("!^3(/.*)?$!", $resource, $m)) {
		assertType('array{0: non-falsy-string, 1?: non-falsy-string}', $m);
		$resource = count($m) > 1 ? substr($m[1], 1) : '';
		assertType('string', $resource);
	}
}

/** @param array{0: string, 1?: string} $m */
function testPhpDocArrayWithCountAndSubstr(array $m): void {
	$x = count($m) > 1 ? substr($m[1], 1) : '';
	assertType('string', $x);
}

/** @param array{0: string, 1?: string} $m */
function testPhpDocArrayWithCountEqualsAndSubstr(array $m): void {
	$x = count($m) === 2 ? substr($m[1], 1) : '';
	assertType('string', $x);
}

/** @param array{0: string, 1?: string} $m */
function testCountNarrowingInIfStatement(array $m): void {
	if (count($m) > 1) {
		$x = substr($m[1], 1);
		assertType('string', $x);
	}
}

/** @param array{0: string, 1?: string} $m */
function testReturnTernary(array $m): string {
	return count($m) > 1 ? substr($m[1], 1) : '';
}

/** @param array{0: string, 1?: string} $m */
function testCountTernaryWithFalsyBranches(array $m): void {
	$x = count($m) > 1 ? false : '';
	assertType("''|false", $x);
}
