<?php // onlyif PHP_VERSION_ID >= 80000

namespace pathinfo;

use function PHPStan\Testing\assertType;

function doFoo(string $s,  int $i) {
	assertType('array{dirname?: string, basename: string, extension?: string, filename: string}', pathinfo($s, PATHINFO_ALL));
}
